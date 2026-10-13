<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\ProcedureStudy;
use App\Support\Audit;
use App\Support\Diagnoses;
use App\Support\Forms;
use App\Support\JalaliCalendar;
use App\Support\JalaliDate;
use App\Support\Procedures;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registry export, like heart's table CSV export (clinic.php?export=csv|raw): one row per study of
 * a form with the patient's and admission's details, then every field of the form, either as the
 * form shows it (labels) or as stored (codes). Streamed, with a UTF-8 BOM so spreadsheets read
 * Persian text. Only this hospital's studies (HospitalScope); needs registry.export and the form's
 * view permission.
 */
class RegistryExportController extends Controller
{
    public function __invoke(Request $request, string $form): StreamedResponse
    {
        abort_unless(in_array($form, ['echo', ...array_keys(Procedures::MODELS)], true), 404);
        abort_unless(Forms::allows($request->user(), $form, 'view'), 403);

        $filters = $request->validate([
            'format' => ['nullable', Rule::in(['labels', 'codes'])],
            'from' => ['nullable', 'string', fn ($attribute, $value, $fail) => JalaliCalendar::isValid(JalaliCalendar::normalize($value)) || $fail(__('Use a Jalali date such as 1405/01/01.'))],
            'to' => ['nullable', 'string', fn ($attribute, $value, $fail) => JalaliCalendar::isValid(JalaliCalendar::normalize($value)) || $fail(__('Use a Jalali date such as 1405/01/01.'))],
            'status' => ['nullable', Rule::enum(StudyStatus::class)],
        ]);
        $labels = ($filters['format'] ?? 'labels') === 'labels';
        $from = $this->day($filters['from'] ?? null)?->startOfDay()->utc();
        $to = $this->day($filters['to'] ?? null)?->endOfDay()->utc();

        /** @var class-string<EchoStudy|ProcedureStudy> $model */
        $model = $form === 'echo' ? EchoStudy::class : Procedures::model($form);
        $fields = $this->fields($form);
        $query = $model::query()
            ->with(['patient', 'admission'])
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));

        $rows = (clone $query)->count();
        Audit::record('registry.exported', null, ['form' => $form, 'format' => $labels ? 'labels' : 'codes', 'filters' => array_filter(array_diff_key($filters, ['format' => 1])), 'rows' => $rows]);

        $name = "{$form}-".str_replace('/', '-', JalaliDate::format(now(), 'yyyy/MM/dd-HHmm')).'.csv';

        return response()->streamDownload(function () use ($query, $fields, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, [
                'study_id', 'status', 'recorded', 'signed',
                'national_id', 'first_name', 'last_name', 'dob', 'gender',
                'admission_his_id', 'admitted_on', 'ward', 'discharged_on',
                ...array_map(fn (string $name, array $field) => $labels ? "{$field['label']} ({$name})" : $name, array_keys($fields), $fields),
            ]);

            $query->lazyById(500)->each(function (EchoStudy|ProcedureStudy $study) use ($out, $fields, $labels) {
                $values = [
                    $study->id, $study->status->value, JalaliDate::format($study->created_at),
                    $study->finalized_at ? JalaliDate::format($study->finalized_at) : '',
                    $study->patient?->national_id, $study->patient?->first_name, $study->patient?->last_name, $study->patient?->dob, $study->patient?->gender,
                    $study->admission?->his_id, $study->admission?->admitted_on, $study->admission?->wardName(), $study->admission?->discharged_on,
                ];
                foreach ($fields as $name => $field) {
                    $values[] = $this->cell($field, $study->getAttribute($name), $labels);
                }
                fputcsv($out, array_map(self::safe(...), $values));
            });

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * A Jalali date as that day in the hospital's display timezone (dates on the forms are local).
     */
    private function day(?string $jalali): ?CarbonImmutable
    {
        $date = JalaliCalendar::toDate(JalaliCalendar::normalize((string) $jalali));

        return $date ? CarbonImmutable::parse($date->toDateString(), config('echo.display_timezone')) : null;
    }

    /**
     * The form's fields, with echo's types in the procedures' shape.
     *
     * @return array<string, array<string, mixed>>
     */
    private function fields(string $form): array
    {
        if ($form !== 'echo') {
            return Procedures::fields($form);
        }

        $fields = [];
        foreach (['echo_date' => 'text', 'doctor' => 'text', ...config('echo.fields')] as $name => $type) {
            $fields[$name] = ['type' => $type, 'label' => $name];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function cell(array $field, mixed $value, bool $labels): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match (true) {
            $field['type'] === 'boolean' => $labels ? ($value ? 'Yes' : 'No') : ($value ? '1' : '0'),
            $field['type'] === 'choice' && $labels => (string) ($field['options'][(string) $value] ?? $value),
            $field['type'] === 'diagnoses' => implode('; ', $labels ? Diagnoses::labels((array) $value) : (array) $value),
            is_float($value) && floor($value) === $value => (string) (int) $value,
            default => (string) $value,
        };
    }

    /**
     * Free text that starts like a formula would be run by the spreadsheet: keep it as text.
     */
    private static function safe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) && ! is_numeric($value) ? "'".$value : $value;
    }
}
