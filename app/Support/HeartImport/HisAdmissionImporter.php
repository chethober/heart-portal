<?php

namespace App\Support\HeartImport;

use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Support\Audit;
use App\Support\CurrentHospital;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Brings admissions from the hospital information system's registry view
 * (heart's include/connect.php read the same view) into one hospital.
 *
 * Patients are matched on national ID: new ones are created, existing ones
 * only get demographics they are missing (staff may have corrected them).
 * Admissions are matched on the HIS admission number (his_id) and updated,
 * so discharge dates arrive once the HIS records them.
 */
final class HisAdmissionImporter
{
    private ImportSummary $summary;

    /**
     * Counts of the row being imported.
     *
     * @var list<array{0: string, 1: string}>
     */
    private array $pending = [];

    public function __construct(
        private readonly Connection $his,
        private readonly Hospital $hospital,
        private readonly string $view,
    ) {
        $this->summary = new ImportSummary('his');
    }

    /**
     * @param  string|null  $since  only admissions on or after this Jalali date (YYYY/MM/DD)
     * @param  int  $limit  most recent admissions to read; 0 for all
     */
    public function run(?string $since = null, int $limit = 0, bool $dryRun = false): ImportSummary
    {
        $current = app(CurrentHospital::class);
        $previous = $current->id();
        $current->set($this->hospital->id);
        $this->summary->add('patients', 'read', 0);
        $this->summary->add('admissions', 'read', 0);

        // Column names are the view's; the query builder quotes them (spaces included) and binds every value.
        $query = $this->his->table($this->view)
            ->select([
                'Adm Code as AdmCode', 'NationalCode', 'Name', 'Family', 'Sex', 'Birth Date as BirthDate',
                'MobileNo', 'Tel', 'Discharge_ward', 'physician', 'Date', 'DisDate',
            ])
            ->when($since !== null, fn ($q) => $q->where('Date', '>=', $since))
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->orderByDesc('Date');

        DB::beginTransaction();

        try {
            foreach ($query->cursor() as $record) {
                $row = new LegacyRow($record);
                $hisId = LegacyValue::code($row->get('AdmCode'));
                $this->summary->add('admissions', 'read');

                try {
                    $this->pending = [];
                    DB::transaction(fn () => $this->import($row, $hisId));

                    // Counted only once the row is saved, so a failed row leaves no counts behind.
                    foreach ($this->pending as [$table, $column]) {
                        $this->summary->add($table, $column);
                    }
                } catch (Throwable $e) {
                    $this->summary->error('admissions', $hisId, $e);
                }
            }

            if (! $dryRun) {
                Audit::record('his.imported', $this->hospital, ['counts' => $this->summary->counts()]);
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

    private function import(LegacyRow $row, ?string $hisId): void
    {
        if ($hisId === null) {
            throw new ImportException('no admission number');
        }

        $patient = $this->patient($row);

        if (! $patient) {
            $this->tally('admissions', 'skipped');

            return;
        }

        $attributes = array_filter([
            'patient_id' => $patient->id,
            'ward' => LegacyValue::string($row->get('Discharge_ward')),
            'physician' => LegacyValue::string($row->get('physician')),
            'admitted_on' => LegacyValue::date($row->get('Date')),
            'discharged_on' => LegacyValue::date($row->get('DisDate')),
        ], fn ($v) => $v !== null);

        $admission = Admission::withTrashed()->where('his_id', $hisId)->first();

        if ($admission?->trashed()) {
            $this->tally('admissions', 'skipped');

            return;
        }

        if (! $admission) {
            $admission = Admission::create([...$attributes, 'his_id' => $hisId]);
            Audit::record('admission.imported', $admission, ['source' => 'his', 'his_id' => $hisId]);
            $this->tally('admissions', 'created');

            return;
        }

        $admission->fill($attributes);

        if (! $admission->isDirty()) {
            $this->tally('admissions', 'skipped');

            return;
        }

        $changes = Audit::diff($admission);
        $admission->save();
        Audit::record('admission.reimported', $admission, ['source' => 'his', 'his_id' => $hisId, 'changes' => $changes]);
        $this->tally('admissions', 'updated');
    }

    private function patient(LegacyRow $row): ?Patient
    {
        $nationalId = LegacyValue::text($row->get('NationalCode'));
        $nationalId = $nationalId === null ? null : LegacyValue::text(Patient::normalizeNationalId($nationalId));

        if ($nationalId === null) {
            throw new ImportException('no national code');
        }

        $this->tally('patients', 'read');
        $attributes = array_filter([
            'first_name' => LegacyValue::string($row->get('Name')),
            'last_name' => LegacyValue::string($row->get('Family')),
            'dob' => LegacyValue::date($row->get('BirthDate')),
            'gender' => LegacyValue::gender($row->get('Sex')),
            'phone_number' => LegacyValue::phone($row->first('MobileNo', 'Tel')),
        ], fn ($v) => $v !== null);

        $patient = Patient::withTrashed()->whereNationalId($nationalId)->first();

        if ($patient?->trashed()) {
            $this->tally('patients', 'skipped');

            return null;
        }

        if (! $patient) {
            if (! isset($attributes['first_name']) && ! isset($attributes['last_name'])) {
                throw new ImportException('no patient name');
            }

            $patient = Patient::create(['first_name' => '', 'last_name' => '', ...$attributes, 'national_id' => $nationalId]);
            Audit::record('patient.imported', $patient, ['source' => 'his']);
            $this->tally('patients', 'created');

            return $patient;
        }

        // Only fill what is missing: names and dates may have been corrected here.
        foreach ($attributes as $attribute => $value) {
            if (LegacyValue::string($patient->getAttribute($attribute)) === null) {
                $patient->setAttribute($attribute, $value);
            }
        }

        if ($patient->isDirty()) {
            $changes = Audit::diff($patient);
            $patient->save();
            Audit::record('patient.reimported', $patient, ['source' => 'his', 'changes' => $changes]);
            $this->tally('patients', 'updated');
        } else {
            $this->tally('patients', 'skipped');
        }

        return $patient;
    }

    private function tally(string $table, string $column): void
    {
        $this->pending[] = [$table, $column];
    }
}
