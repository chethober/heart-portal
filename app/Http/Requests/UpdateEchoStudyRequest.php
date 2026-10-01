<?php

namespace App\Http\Requests;

use App\Models\EchoStudy;

class UpdateEchoStudyRequest extends StoreEchoStudyRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->study());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'amendment_reason' => $this->study()->isSigned()
                ? ['required', 'string', 'min:5', 'max:2000']
                : ['prohibited'],
        ];
    }

    public function study(): EchoStudy
    {
        return $this->route('study');
    }
}
