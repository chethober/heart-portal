<?php

namespace App\Http\Requests\Concerns;

use App\Models\Patient;
use Illuminate\Validation\Validator;

/**
 * The patient demographics every study form posts, named as the echo form names them.
 */
trait HasPatientFields
{
    protected const JALALI_DATE = 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/';

    /**
     * Normalizes the national ID and the given Jalali date fields (Persian digits, separators).
     *
     * @param  list<string>  $dateFields
     */
    protected function normalizePatientInput(array $dateFields): void
    {
        $normalized = [];

        if (is_string($this->input('nationalID'))) {
            $normalized['nationalID'] = Patient::normalizeNationalId($this->input('nationalID'));
        }

        foreach (['dob', ...$dateFields] as $dateField) {
            if (is_string($this->input($dateField))) {
                $normalized[$dateField] = Patient::normalizeNationalId($this->input($dateField)) ?: null;
            }
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    protected function patientRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nationalID' => ['required', 'string', 'max:32', 'regex:/^[0-9A-Za-z]+$/'],
            'dob' => ['nullable', 'string', self::JALALI_DATE],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'in:Male,Female'],
        ];
    }

    /**
     * Rejects any posted field without a rule, so nothing unexpected reaches the database.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // ?context= is the taskbar tab a new study was started in (StudyContext).
                $known = [...array_keys($this->rules()), '_token', '_method', 'context'];

                foreach (array_diff(array_keys($this->all()), $known) as $unknown) {
                    $validator->errors()->add($unknown, __('Unknown field :field.', ['field' => $unknown]));
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function patientAttributes(): array
    {
        return [
            'national_id' => $this->validated('nationalID'),
            'first_name' => $this->validated('first_name'),
            'last_name' => $this->validated('last_name'),
            'dob' => $this->validated('dob'),
            'gender' => $this->validated('gender'),
            'phone_number' => $this->validated('phone_number'),
        ];
    }
}
