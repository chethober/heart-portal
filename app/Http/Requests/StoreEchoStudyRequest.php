<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates the JSON the echo form posts. Only fields listed in
 * config/echo.php are accepted; anything else fails validation instead of
 * reaching the database.
 */
class StoreEchoStudyRequest extends FormRequest
{
    private const JALALI_DATE = 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/';

    public function authorize(): bool
    {
        return $this->user()->can('studies.create');
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('nationalID'))) {
            $normalized['nationalID'] = Patient::normalizeNationalId($this->input('nationalID'));
        }

        foreach (['dob', 'echo_date'] as $dateField) {
            if (is_string($this->input($dateField))) {
                $normalized[$dateField] = Patient::normalizeNationalId($this->input($dateField)) ?: null;
            }
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'nationalID' => ['required', 'string', 'max:32', 'regex:/^[0-9A-Za-z]+$/'],
            'dob' => ['nullable', 'string', self::JALALI_DATE],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'in:Male,Female'],
            'echo_date' => ['nullable', 'string', self::JALALI_DATE],
            'doctor' => ['nullable', 'string', 'max:255'],
        ];

        foreach (config('echo.fields') as $field => $type) {
            $rules[$field] = match ($type) {
                'number' => ['nullable', 'numeric', 'between:-100000,100000'],
                'boolean' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:5000'],
            };
        }

        return $rules;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $known = [...array_keys($this->rules()), '_token', '_method'];

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

    /**
     * @return array<string, mixed>
     */
    public function studyAttributes(): array
    {
        return $this->safe()->only([...config('echo.meta_fields'), ...array_keys(config('echo.fields'))]);
    }
}
