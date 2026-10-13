<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('auth.accept-invitation', ['invitation' => $this->pendingInvitation($token)]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->pendingInvitation($token);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'medical_license_no' => ['nullable', 'string', 'max:64'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        abort_if(User::where('email', $invitation->email)->exists(), 409, __('An account with this email already exists.'));

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'hospital_id' => $invitation->hospital_id,
                'name' => $request->name,
                'email' => $invitation->email,
                'medical_license_no' => $request->medical_license_no,
                'password' => $request->password,
            ]);
            // The invite link was delivered to this address, which proves ownership.
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignStaffRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);
            Audit::record('staff.joined', $user, ['role' => $invitation->role->value], $user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function pendingInvitation(string $token): Invitation
    {
        $invitation = Invitation::withoutGlobalScope(HospitalScope::class)
            ->with('hospital')
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->isPending(), 410, __('This invitation has expired or was already used.'));

        return $invitation;
    }
}
