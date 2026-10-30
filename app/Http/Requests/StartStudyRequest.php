<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasPatientFields;
use App\Support\Forms;
use App\Support\JalaliCalendar;
use App\Support\Procedures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartStudyRequest extends FormRequest
{
    use HasPatientFields;

    public function authorize(): bool
    {
        $study = $this->input('study');

        return is_string($study) && in_array($study, ['echo', ...array_keys(Procedures::MODELS)], true)
            && Forms::allows($this->user(), $study, 'create');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePatientInput(['study_date']);
        foreach (['dob', 'study_date'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => JalaliCalendar::normalize($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        $date = ['nullable', 'string', self::JALALI_DATE, function (string $attribute, mixed $value, \Closure $fail) {
            if (! JalaliCalendar::isValid($value)) {
                $fail(__('This is not a valid date.'));
            }
        }];

        return [
            ...$this->patientRules(),
            'dob' => $date,
            'study_date' => $date,
            'admission_number' => ['nullable', 'string', 'max:255'],
            'replaces' => ['nullable', 'string'],
            'study' => ['required', Rule::in(['echo', ...array_keys(Procedures::MODELS)])],
        ];
    }
}
