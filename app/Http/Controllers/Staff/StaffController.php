<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Audit;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.staff', [
            'staff' => $request->user()->hospital->users()->with('roles')->orderBy('name')->get(),
            'invitations' => Invitation::pending()->latest()->get(),
            'roles' => Role::assignableByHospital(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hospital_id === $request->user()->hospital_id, 404);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)->only(Role::assignableByHospital())],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && ($validated['role'] !== Role::HospitalAdmin->value || ! $validated['is_active'])) {
            return back()->withErrors(['staff' => __('You cannot remove your own admin access.')]);
        }

        $previousRole = $user->primaryRole()?->value;
        $user->fill(['is_active' => $validated['is_active']]);
        $changes = Audit::diff($user);
        $user->save();
        $user->syncRoles([$validated['role']]);

        if ($previousRole !== $validated['role']) {
            $changes['role'] = [$previousRole, $validated['role']];
        }
        if ($changes) {
            Audit::record('staff.updated', $user, $changes);
        }

        return back()->with('status', __('Updated :name.', ['name' => $user->name]));
    }

    /**
     * For staff who lost their authenticator device: they must set it up again at next login.
     */
    public function resetTwoFactor(Request $request, User $user, TwoFactor $twoFactor): RedirectResponse
    {
        abort_unless($user->hospital_id === $request->user()->hospital_id, 404);

        $twoFactor->disable($user);
        Audit::record('staff.two_factor_reset', $user);

        return back()->with('status', __('Two-factor login reset for :name.', ['name' => $user->name]));
    }
}
