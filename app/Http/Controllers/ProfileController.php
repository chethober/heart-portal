<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('hospital'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // The license number is printed under the signature on reports, so changes are audited.
        if ($user->isDirty('medical_license_no')) {
            Audit::record('user.profile_updated', $user, ['medical_license_no' => [$user->getOriginal('medical_license_no'), $user->medical_license_no]]);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * For users who no longer remember their current password: a reset link to the address on file.
     */
    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $user = $request->user();
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()->to(route('profile.edit').'#password')->withErrors(['email' => __($status)], 'passwordReset');
        }

        Audit::record('user.password_reset_requested', $user);

        return redirect()->to(route('profile.edit').'#password')->with('status', 'password-reset-link-sent');
    }
}
