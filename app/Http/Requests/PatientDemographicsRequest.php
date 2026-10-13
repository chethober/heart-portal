<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the patient record form: the demographics study forms share, plus the
 * details from heart's patient form. The national ID is not editable here.
 */
class PatientDemographicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('patients.edit');
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('dob'))) {
            $normalized['dob'] = JalaliCalendar::normalize($this->input('dob'));
        }

        foreach (['phone_number', 'home_phone', 'emergency_phone'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = Patient::normalizeNationalId($this->input($field)) ?: null;
            }
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $phone = ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9()]+$/'];

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'string', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/', function (string $attribute, mixed $value, \Closure $fail) {
                if (! JalaliCalendar::isValid($value)) {
                    $fail(__('This is not a valid date.'));
                }
            }],
            'gender' => ['nullable', 'in:Male,Female'],
            'nationality' => ['nullable', 'string', Rule::in(array_map('strval', array_keys(Patient::nationalities())))],
            'nationality_other' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'integer', Rule::in(array_keys(Patient::maritalStatuses()))],
            'occupation' => ['nullable', 'string', 'max:255'],
            'phone_number' => $phone,
            'home_phone' => $phone,
            'emergency_phone' => $phone,
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function patientAttributes(): array
    {
        $attributes = $this->validated();

        if (($attributes['nationality'] ?? null) !== '99999') {
            $attributes['nationality_other'] = null;
        }

        return $attributes;
    }
}
