<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasPatientFields;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the JSON the echo form posts. Only fields listed in
 * config/echo.php are accepted; anything else fails validation instead of
 * reaching the database.
 */
class StoreEchoStudyRequest extends FormRequest
{
    use HasPatientFields;

    public function authorize(): bool
    {
        return $this->user()->can('studies.create');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePatientInput(['echo_date']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            ...$this->patientRules(),
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
     * @return array<string, mixed>
     */
    public function studyAttributes(): array
    {
        return $this->safe()->only([...config('echo.meta_fields'), ...array_keys(config('echo.fields'))]);
    }
}
