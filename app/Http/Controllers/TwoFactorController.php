<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Enrolment and management of the user's authenticator app (profile page).
 */
class TwoFactorController extends Controller
{
    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $twoFactor->enable($request->user());

        return redirect()->to(route('profile.edit').'#two-factor')->with('status', 'two-factor-pending');
    }

    public function confirm(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['code' => ['required', 'string']]);
        $user = $request->user();

        if ($user->two_factor_secret === null || ! $twoFactor->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => __('The provided two-factor code was invalid.')])->errorBag('twoFactor');
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        Audit::record('user.two_factor_enabled', $user);

        return redirect()->to(route('profile.edit').'#two-factor')
            ->with('status', 'two-factor-confirmed')
            ->with('showRecoveryCodes', true);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $twoFactor->regenerateRecoveryCodes($request->user());
        Audit::record('user.recovery_codes_regenerated', $request->user());

        return redirect()->to(route('profile.edit').'#two-factor')->with('showRecoveryCodes', true);
    }

    public function destroy(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']]);

        $twoFactor->disable($request->user());
        Audit::record('user.two_factor_disabled', $request->user());

        return redirect()->to(route('profile.edit').'#two-factor')->with('status', 'two-factor-disabled');
    }
}
