<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Models\Prescription;
use App\Support\Prescribing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a prescription draft. Linked records (patient, admission, discharge summary, catalog
 * drugs) are only checked for shape here; the controller looks them up through the tenant scopes,
 * because exists: rules would accept another hospital's ids.
 */
class PrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $prescription = $this->route('prescription');

        return $prescription instanceof Prescription
            ? $this->user()->can('update', $prescription)
            : $this->user()->can('prescriptions.create');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('prescribed_on'))) {
            $this->merge(['prescribed_on' => Patient::normalizeNationalId($this->input('prescribed_on')) ?: null]);
        }

        // Rows the user added but left empty are ignored.
        if (is_array($this->input('items'))) {
            $this->merge(['items' => array_values(array_filter($this->input('items'), fn ($item) => is_array($item)
                && (filled($item['drug_id'] ?? null) || filled($item['drug_name'] ?? null))))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = ! $this->route('prescription') instanceof Prescription;

        return [
            'patient_id' => $creating ? ['required_without:discharge_study_id', 'nullable', 'integer'] : ['prohibited'],
            'discharge_study_id' => $creating ? ['nullable', 'integer'] : ['prohibited'],
            'admission_id' => ['nullable', 'integer'],
            'prescribed_on' => ['nullable', 'string', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.drug_id' => ['nullable', 'integer'],
            'items.*.drug_name' => ['required_without:items.*.drug_id', 'nullable', 'string', 'max:255'],
            'items.*.dose' => ['nullable', 'string', 'max:32'],
            'items.*.unit' => ['nullable', 'string', Rule::in(Prescribing::UNITS)],
            'items.*.route' => ['nullable', 'string', Rule::in(array_keys(Prescribing::ROUTES))],
            'items.*.frequency' => ['nullable', 'string', Rule::in(array_keys(Prescribing::FREQUENCIES))],
            'items.*.duration' => ['nullable', 'string', 'max:64'],
            'items.*.quantity' => ['nullable', 'integer', 'between:1,1000'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => __('Add at least one drug.'),
            'items.*.drug_name.required_without' => __('Pick a drug from the catalog or type its name.'),
        ];
    }
}
