<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The staff access grid: the form permissions to grant one member, at most their role's ceiling.
 */
class StaffFormPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('staff.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::in($this->member()->formCeiling())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['permissions.*.in' => __("That access is beyond the member's role.")];
    }

    /**
     * @return list<string>
     */
    public function granted(): array
    {
        return array_values($this->validated('permissions', []));
    }

    private function member(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
