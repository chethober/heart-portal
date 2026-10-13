<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasPatientFields;
use App\Models\ProcedureStudy;
use App\Support\Diagnoses;
use App\Support\Forms;
use App\Support\Procedures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a procedure form (create, edit or amend). Only fields listed in the
 * procedure's config are accepted, and choices must be one of its codes.
 */
class ProcedureStudyRequest extends FormRequest
{
    use HasPatientFields;

    public function authorize(): bool
    {
        $study = $this->study();

        return $study ? $this->user()->can('update', $study) : Forms::allows($this->user(), $this->route('procedure'), 'create');
    }

    protected function prepareForValidation(): void
    {
        $dates = array_keys(array_filter($this->fields(), fn (array $field) => $field['type'] === 'date'));

        $this->normalizePatientInput($dates);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            ...$this->patientRules(),
            'admission_id' => ['nullable', 'integer', Rule::exists('admissions', 'id')
                ->where('hospital_id', $this->user()->hospital_id)
                ->whereNull('deleted_at')],
        ];

        foreach ($this->fields() as $name => $field) {
            $rules[$name] = match ($field['type']) {
                'choice' => ['nullable', 'string', Rule::in(array_map('strval', array_keys($field['options'])))],
                'number' => ['nullable', 'numeric', 'between:-100000,100000'],
                'boolean' => ['nullable', 'boolean'],
                'date' => ['nullable', 'string', self::JALALI_DATE],
                'time' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
                'diagnoses' => ['nullable', 'array', 'max:20'],
                default => ['nullable', 'string', 'max:5000'],
            };
        }

        foreach ($this->fields() as $name => $field) {
            if ($field['type'] === 'diagnoses') {
                $rules["{$name}.*"] = ['string', 'distinct', Rule::in(Diagnoses::keys())];
            }
        }

        $rules['amendment_reason'] = $this->study()?->isSigned()
            ? ['required', 'string', 'min:5', 'max:2000']
            : ['prohibited'];

        return $rules;
    }

    /**
     * Every field of the procedure. Fields left out of the form (an unticked box)
     * become null, as do fields hidden because the field they depend on changed.
     *
     * @return array<string, mixed>
     */
    public function studyAttributes(): array
    {
        $values = [];

        foreach ($this->fields() as $name => $field) {
            $value = $this->validated($name);

            if (isset($field['show_if']) && (string) ($values[$field['show_if'][0]] ?? '') !== $field['show_if'][1]) {
                $value = null;
            }

            $values[$name] = $value === '' || $value === [] ? null : $value;
        }

        return ['admission_id' => $this->validated('admission_id'), ...$values];
    }

    public function study(): ?ProcedureStudy
    {
        return $this->route('procedureStudy');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(): array
    {
        return Procedures::fields($this->route('procedure'));
    }
}
