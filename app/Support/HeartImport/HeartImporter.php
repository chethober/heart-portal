<?php

namespace App\Support\HeartImport;

use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\ProcedureStudy;
use App\Support\Audit;
use App\Support\CurrentHospital;
use App\Support\Diagnoses;
use App\Support\Procedures;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Copies heart's patients, admissions and studies into one hospital.
 *
 * Re-runnable: admissions and studies are matched on legacy_id (heart's row
 * id), patients on national ID. Imported studies are signed history: status
 * final, no signing user, and a version with the reason "Imported from heart".
 * heart's users (tbusers) are never read.
 */
final class HeartImporter
{
    public const IMPORTED = 'Imported from heart';

    public const REIMPORTED = 'Re-imported from heart';

    private ImportSummary $summary;

    /**
     * heart patientId => normalised national ID (null when heart has none).
     *
     * @var array<string, string|null>
     */
    private array $nationalIds = [];

    /**
     * National ID hashes already imported in this run, to merge duplicate heart patients.
     *
     * @var array<string, true>
     */
    private array $seenPatients = [];

    /**
     * heart admitIds of the chosen center; null when not filtering.
     *
     * @var array<string, true>|null
     */
    private ?array $centerAdmits = null;

    /**
     * @var array<string, array<string, string|null>>
     */
    private array $lookups = [];

    public function __construct(
        private readonly Connection $heart,
        private readonly Hospital $hospital,
        private readonly ?string $center = null,
        private readonly bool $allCenters = false,
        private readonly int $chunk = 500,
    ) {
        $this->summary = new ImportSummary('heart');
    }

    /**
     * @param  list<string>  $only  keys of config('heart.tables') to import; all when empty
     */
    public function run(array $only = [], bool $dryRun = false): ImportSummary
    {
        $current = app(CurrentHospital::class);
        $previous = $current->id();
        $current->set($this->hospital->id);

        DB::beginTransaction();

        try {
            $this->checkCenters();

            foreach (array_keys(config('heart.tables')) as $key) {
                if ($only && ! in_array($key, $only, true)) {
                    continue;
                }

                $this->importTable($key);
            }

            if (! $dryRun) {
                Audit::record('heart.imported', $this->hospital, ['center' => $this->center, 'counts' => $this->summary->counts()]);
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        } finally {
            $current->set($previous);
        }

        return $this->summary;
    }

    private function importTable(string $key): void
    {
        ['table' => $table, 'pk' => $pk] = config("heart.tables.{$key}");

        if (! $this->heart->getSchemaBuilder()->hasTable($table)) {
            $this->summary->note("{$key}: heart has no `{$table}` table, skipped.");

            return;
        }

        $this->summary->add($key, 'read', 0);
        $query = $this->heart->table($table);

        if ($key === 'admissions' && $this->center !== null) {
            $query->where('centerId', $this->center);
        }

        foreach ($query->lazyById($this->chunk, $pk) as $record) {
            $row = new LegacyRow($record);
            $id = $row->get($pk);

            if (! $this->inCenter($key, $row)) {
                continue;
            }

            $this->summary->add($key, 'read');

            try {
                // A savepoint per row: a failed row is rolled back alone and the run goes on.
                $result = DB::transaction(fn () => match ($key) {
                    'patients' => $this->patient($row, $id),
                    'admissions' => $this->admission($row, $id),
                    default => $this->study($key, $row, $id),
                });
                $this->summary->add($key, $result);
            } catch (Throwable $e) {
                $this->summary->error($key, $id, $e);
            }
        }
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    private function patient(LegacyRow $row, mixed $id): string
    {
        $nationalId = $this->normalNationalId($row->get('nationalcode'));

        if ($nationalId === null) {
            throw new ImportException('no national code');
        }

        $hash = Patient::hashNationalId($nationalId);

        // Heart allows several patient rows with one national code; they become one patient.
        if (isset($this->seenPatients[$hash])) {
            $this->summary->warning('patients', $id, 'same national code as an earlier heart patient; merged into it');

            return 'skipped';
        }

        $this->seenPatients[$hash] = true;

        $attributes = $this->present($row, [
            'first_name' => ['patientName', fn ($v) => LegacyValue::string($v)],
            'last_name' => ['patientFamily', fn ($v) => LegacyValue::string($v)],
            'dob' => ['DateOf_dateOfBirth', fn ($v) => $this->date('patients', $id, $v)],
            'gender' => ['gender', fn ($v) => LegacyValue::gender($v)],
        ]);
        $phone = LegacyValue::phone($row->first('patientMobile', 'patientTel'));

        if ($phone !== null) {
            $attributes['phone_number'] = $phone;
        }

        $patient = Patient::withTrashed()->where('national_id_hash', $hash)->first();

        if ($patient?->trashed()) {
            return 'skipped';
        }

        // Heart's value wins, but a value heart lacks never blanks one already here.
        $attributes = array_filter($attributes, fn ($v) => $v !== null);

        if (! $patient) {
            if (! isset($attributes['first_name']) && ! isset($attributes['last_name'])) {
                throw new ImportException('no name');
            }

            $patient = Patient::create(['first_name' => '', 'last_name' => '', ...$attributes, 'national_id' => $nationalId]);
            Audit::record('patient.imported', $patient, ['source' => 'heart', 'legacy_id' => $id]);

            return 'created';
        }

        return $this->saveChanges($patient->fill($attributes), 'patient.reimported', $id);
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    private function admission(LegacyRow $row, mixed $id): string
    {
        $patient = $this->patientFor($row);

        if (! $patient) {
            throw new ImportException('its patient was not imported (no national code, or patients not imported yet)');
        }

        $hisId = LegacyValue::code($row->get('AdmCode'), ['0']);
        $attributes = array_filter([
            'patient_id' => $patient->id,
            'ward' => $this->lookup('ward', 'wardId', $row->get('ward'), fn (LegacyRow $ward) => $ward->get('Title')),
            'physician' => $this->lookup('physician', 'shnezam', $row->get('physician'), fn (LegacyRow $doctor) => implode(' ', array_filter([
                LegacyValue::string($doctor->get('title')), LegacyValue::string($doctor->get('firstname')), LegacyValue::string($doctor->get('lastname')),
            ]))),
            'admitted_on' => $this->date('admissions', $id, $row->get('DateOf_admitdate')),
            // Heart's admit table names the discharge date three ways across versions.
            'discharged_on' => $this->date('admissions', $id, $row->first('DateOf_dischargedate', 'DateOf_disch_date', 'DateOf_DisDate')),
            // Heart's ICD-10 codes, as far as they match the final-diagnosis list.
            'icd_codes' => Diagnoses::fromIcdCodes(LegacyValue::string($row->get('ICDCodes'))) ?: null,
        ], fn ($v) => $v !== null);

        $admission = Admission::withTrashed()->where('legacy_id', $id)->first()
            // Already brought in by his:import-admissions: adopt it.
            ?? ($hisId !== null ? Admission::withTrashed()->where('his_id', $hisId)->whereNull('legacy_id')->first() : null);

        if ($admission?->trashed()) {
            return 'skipped';
        }

        $taken = $hisId !== null && Admission::withTrashed()->where('his_id', $hisId)
            ->when($admission, fn ($query) => $query->whereKeyNot($admission->id))
            ->exists();

        if ($taken) {
            $this->summary->warning('admissions', $id, "HIS admission number {$hisId} is already used by another admission; not set");
            $hisId = null;
        }

        if ($hisId !== null) {
            $attributes['his_id'] = $hisId;
        }

        if (! $admission) {
            $admission = Admission::create([...$attributes, 'legacy_id' => $id]);
            Audit::record('admission.imported', $admission, ['source' => 'heart', 'legacy_id' => $id]);

            return 'created';
        }

        return $this->saveChanges($admission->fill([...$attributes, 'legacy_id' => $id]), 'admission.reimported', $id);
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    private function study(string $key, LegacyRow $row, mixed $id): string
    {
        $model = $key === 'echo' ? EchoStudy::class : Procedures::model($key);
        $values = $key === 'echo' ? $this->echoValues($row, $id) : $this->procedureValues($key, $row, $id);

        $admission = LegacyValue::text($row->get('admitId')) !== null
            ? Admission::where('legacy_id', LegacyValue::code($row->get('admitId')))->first()
            : null;
        $patientId = $admission->patient_id ?? $this->patientFor($row)?->id;

        if ($patientId === null) {
            throw new ImportException('its admission or patient was not imported');
        }

        /** @var EchoStudy|ProcedureStudy|null $study */
        $study = $model::withTrashed()->where('legacy_id', $id)->first();

        // Deleted in Heart Portal, or amended here since: the portal's version stands.
        if ($study && ($study->trashed() || $study->status !== StudyStatus::Final)) {
            return 'skipped';
        }

        $study ??= new $model;
        $study->fill([...$values, 'patient_id' => $patientId, 'admission_id' => $admission?->id, 'legacy_id' => $id]);

        if ($study->exists) {
            if (! $study->isDirty()) {
                return 'skipped';
            }

            $changes = Audit::diff($study);
            $study->save();
            $this->recordVersion($study, self::REIMPORTED);
            Audit::record($this->auditPrefix($key).'.reimported', $study, $changes);

            return 'updated';
        }

        $created = $this->timestamp($row->get('auto_createtime')) ?? now();
        $study->forceFill([
            'status' => StudyStatus::Final,
            'finalized_at' => $created,
            'created_at' => $created,
            'updated_at' => $this->timestamp($row->get('auto_lastupdatetime')) ?? $created,
        ])->save();
        $this->recordVersion($study, self::IMPORTED);
        Audit::record($this->auditPrefix($key).'.imported', $study, ['source' => 'heart', 'legacy_id' => $id]);

        return 'created';
    }

    /**
     * Heart's values for a procedure's fields, keyed by Heart Portal column. Columns
     * heart's table does not have are left out.
     *
     * @return array<string, mixed>
     */
    private function procedureValues(string $key, LegacyRow $row, mixed $id): array
    {
        $values = [];

        foreach (Procedures::fields($key) as $column => $field) {
            $source = $field['legacy'] ?? $column;

            if (! $row->has($source)) {
                continue;
            }

            $raw = $row->get($source);
            $value = match ($field['type']) {
                'choice' => LegacyValue::code($raw, $field['blank'] ?? []),
                'number' => LegacyValue::number($raw),
                'boolean' => LegacyValue::boolean($raw),
                'date' => LegacyValue::date($raw),
                'time' => LegacyValue::time($raw),
                default => LegacyValue::string($raw),
            };

            if ($value === null && $field['type'] !== 'choice' && $field['type'] !== 'boolean' && LegacyValue::text($raw) !== null) {
                $this->summary->warning($key, $id, "{$source} value could not be read as {$field['type']}");
            }

            $values[$column] = $value;
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function echoValues(LegacyRow $row, mixed $id): array
    {
        $mapping = new EchoMapping;
        $values = $mapping->map($row);

        foreach ($mapping->warnings() as $warning) {
            $this->summary->warning('echo', $id, $warning);
        }

        return $values;
    }

    private function recordVersion(EchoStudy|ProcedureStudy $study, string $reason): void
    {
        $study->versions()->create([
            'hospital_id' => $study->hospital_id,
            'version' => ($study->versions()->max('version') ?? 0) + 1,
            'status' => $study->status,
            'data' => $study->load('patient')->toReportArray(),
            'signed_by' => null,
            'signature_path' => null,
            'reason' => $reason,
        ]);
    }

    private function auditPrefix(string $key): string
    {
        // Echo studies are audited as "study.*" throughout the app.
        return $key === 'echo' ? 'study' : $key;
    }

    /**
     * @return 'updated'|'skipped'
     */
    private function saveChanges(Model $model, string $action, mixed $id): string
    {
        if (! $model->isDirty()) {
            return 'skipped';
        }

        $changes = Audit::diff($model);
        $model->save();
        Audit::record($action, $model, ['source' => 'heart', 'legacy_id' => $id, 'changes' => $changes]);

        return 'updated';
    }

    /**
     * The Heart Portal patient for a heart admit or study row: through heart's
     * patientId, else the row's own national code.
     */
    private function patientFor(LegacyRow $row): ?Patient
    {
        $nationalId = null;
        $heartPatientId = LegacyValue::code($row->get('patientId'));

        if ($heartPatientId !== null) {
            $nationalId = $this->nationalIds[$heartPatientId] ??= $this->normalNationalId(
                $this->heart->table(config('heart.tables.patients.table'))
                    ->where(config('heart.tables.patients.pk'), $heartPatientId)
                    ->value('nationalcode'),
            );
        }

        $nationalId ??= $this->normalNationalId($row->get('nationalcode'));

        return $nationalId === null ? null : Patient::whereNationalId($nationalId)->first();
    }

    private function normalNationalId(mixed $value): ?string
    {
        $text = LegacyValue::text($value);

        return $text === null ? null : (LegacyValue::text(Patient::normalizeNationalId($text)) ?? null);
    }

    /**
     * Display text for a code in one of heart's lookup tables (ward, physician),
     * or the code itself when heart's database does not have the table or row.
     *
     * @param  callable(LegacyRow): mixed  $display
     */
    private function lookup(string $table, string $key, mixed $code, callable $display): ?string
    {
        $code = LegacyValue::code($code, ['-1']);

        if ($code === null) {
            return null;
        }

        if (! array_key_exists($table, $this->lookups)) {
            $this->lookups[$table] = $this->heart->getSchemaBuilder()->hasTable($table) ? [] : null;
        }

        if ($this->lookups[$table] === null) {
            return $code;
        }

        if (! array_key_exists($code, $this->lookups[$table])) {
            $record = $this->heart->table($table)->where($key, $code)->first();
            $this->lookups[$table][$code] = $record ? LegacyValue::string($display(new LegacyRow($record))) : null;
        }

        return $this->lookups[$table][$code] ?? $code;
    }

    private function date(string $table, mixed $id, mixed $value): ?string
    {
        $date = LegacyValue::date($value);

        if ($date === null && LegacyValue::text($value) !== null) {
            $this->summary->warning($table, $id, 'a date could not be read');
        }

        return $date;
    }

    private function timestamp(mixed $value): ?Carbon
    {
        $text = LegacyValue::text($value);

        if ($text === null || str_starts_with($text, '0000')) {
            return null;
        }

        try {
            return Carbon::parse($text, config('echo.display_timezone'))->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, array{0: string, 1: callable(mixed): mixed}>  $map  attribute => [heart column, converter]
     * @return array<string, mixed>
     */
    private function present(LegacyRow $row, array $map): array
    {
        $attributes = [];

        foreach ($map as $attribute => [$column, $convert]) {
            if ($row->has($column)) {
                $attributes[$attribute] = $convert($row->get($column));
            }
        }

        return $attributes;
    }

    /**
     * With --center, only admissions of that heart center (admit.centerId) and their patients and studies.
     */
    private function inCenter(string $key, LegacyRow $row): bool
    {
        if ($this->center === null || $key === 'admissions') {
            return true;
        }

        if ($this->centerAdmits === null) {
            $this->centerAdmits = [];
            $patients = [];

            foreach ($this->heart->table(config('heart.tables.admissions.table'))->where('centerId', $this->center)->select(['admitId', 'patientId'])->cursor() as $admit) {
                $this->centerAdmits['a'.LegacyValue::code($admit->admitId)] = true;
                $patients['p'.LegacyValue::code($admit->patientId)] = true;
            }

            $this->centerAdmits += $patients;
        }

        return $key === 'patients'
            ? isset($this->centerAdmits['p'.LegacyValue::code($row->get('patientId'))])
            : isset($this->centerAdmits['a'.LegacyValue::code($row->get('admitId'))]);
    }

    /**
     * A heart database may serve several hospitals (admit.centerId). Importing all
     * of them into one tenant would show one hospital's patients to another, so
     * that needs a center or an explicit "all centers".
     */
    private function checkCenters(): void
    {
        $admit = config('heart.tables.admissions.table');
        $schema = $this->heart->getSchemaBuilder();

        if ($this->center !== null || $this->allCenters || ! $schema->hasTable($admit) || ! $schema->hasColumn($admit, 'centerId')) {
            return;
        }

        $centers = $this->heart->table($admit)->whereNotNull('centerId')->distinct()->orderBy('centerId')->pluck('centerId');

        if ($centers->count() > 1) {
            throw new ImportException('heart holds admissions of several centers ('.$centers->implode(', ').'). Choose one with --center, or pass --all-centers to import them all into this hospital.');
        }
    }
}
