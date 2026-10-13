<?php

namespace App\Http\Requests;

use App\Models\Admission;
use App\Support\Diagnoses;
use App\Support\JalaliCalendar;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates heart's admission form (create or edit). Wards and HIS ids are
 * checked against the user's own hospital.
 */
class AdmissionRequest extends FormRequest
{
    private const DATES = ['admitted_on', 'discharged_on'];

    private const TIMES = ['admitted_at', 'discharged_at'];

    public function authorize(): bool
    {
        return $this->user()->can($this->route('admission') ? 'admission.edit' : 'admission.create');
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ([...self::DATES, ...self::TIMES, 'his_id'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = JalaliCalendar::normalize($this->input($field));
            }
        }

        if (is_array($this->input('his_codes'))) {
            $normalized['his_codes'] = array_map(
                fn ($value) => is_string($value) ? JalaliCalendar::normalize($value) : $value,
                $this->input('his_codes'),
            );
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hospitalId = $this->user()->hospital_id;
        $date = ['string', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/', $this->realDate(...)];
        $time = ['nullable', 'string', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'];

        $rules = [
            'ward_id' => ['nullable', 'integer', Rule::exists('wards', 'id')->where('hospital_id', $hospitalId)],
            'admitted_on' => ['required', ...$date],
            'admitted_at' => $time,
            'admission_type' => ['nullable', 'integer', Rule::in(array_keys(Admission::admissionTypes()))],
            'arrival_mode' => ['nullable', 'integer', Rule::in(array_keys(Admission::arrivalModes()))],
            'referred_from' => ['nullable', 'string', 'max:255'],
            'physician' => ['nullable', 'string', 'max:255'],
            'discharged_on' => ['nullable', ...$date],
            'discharged_at' => $time,
            'his_id' => ['nullable', 'string', 'max:64', Rule::unique('admissions', 'his_id')->where('hospital_id', $hospitalId)->ignore($this->route('admission'))],
            'his_codes' => ['nullable', 'array:'.implode(',', Admission::HIS_CODES)],
            // Final diagnoses from heart's ICD guide (heart's admit.ICDCodes).
            'icd_codes' => ['nullable', 'array', 'max:20'],
            'icd_codes.*' => ['string', 'distinct', Rule::in(Diagnoses::keys())],
        ];

        foreach (Admission::HIS_CODES as $code) {
            $rules["his_codes.{$code}"] = ['nullable', 'string', 'max:64'];
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
                $admitted = JalaliCalendar::toDate($this->input('admitted_on'));
                $discharged = JalaliCalendar::toDate($this->input('discharged_on'));

                if ($admitted && $discharged && $discharged < $admitted) {
                    $validator->errors()->add('discharged_on', __('The discharge date cannot be before the admission date.'));
                }
            },
        ];
    }

    /**
     * The admission columns, with the ward's name kept alongside its id.
     *
     * @return array<string, mixed>
     */
    public function admissionAttributes(): array
    {
        $attributes = $this->safe()->except('his_codes');
        $codes = array_filter((array) $this->validated('his_codes'), fn ($value) => filled($value));

        return [
            ...$attributes,
            'admission_type' => $attributes['admission_type'] ?? null,
            'arrival_mode' => $attributes['arrival_mode'] ?? null,
            // Heart asks for the referring hospital only for transfers in.
            'referred_from' => (int) ($attributes['arrival_mode'] ?? 0) === 3 ? ($attributes['referred_from'] ?? null) : null,
            'his_codes' => $codes ?: null,
            // An unticked list is not posted: clear the diagnoses then.
            'icd_codes' => $this->validated('icd_codes') ?: null,
        ];
    }

    private function realDate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! JalaliCalendar::isValid(is_string($value) ? $value : null)) {
            $fail(__('This is not a valid date.'));
        }
    }
}
