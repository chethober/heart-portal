<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\StaffInvitation;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::enum(Role::class)->only(Role::assignableByHospital())],
        ]);

        // Re-inviting the same address replaces any earlier pending invitation.
        Invitation::pending()->where('email', $validated['email'])->delete();

        $invitation = Invitation::create([
            'invited_by' => $request->user()->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)->notify(new StaffInvitation($invitation));
        Audit::record('staff.invited', $invitation, ['email' => $invitation->email, 'role' => $invitation->role->value]);

        return back()->with('status', __('Invitation sent to :email.', ['email' => $invitation->email]));
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $invitation->delete();
        Audit::record('staff.invitation_revoked', $invitation, ['email' => $invitation->email]);

        return back()->with('status', __('Invitation revoked.'));
    }
}
