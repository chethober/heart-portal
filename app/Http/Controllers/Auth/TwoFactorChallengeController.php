<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Second step of login for users with two-factor enabled.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $passed = $request->filled('recovery_code')
            ? $twoFactor->useRecoveryCode($user, $request->string('recovery_code')->toString())
            : $twoFactor->verify($user, $request->string('code')->toString());

        if (! $passed) {
            Audit::record('auth.two_factor_failed', null, [], $user);

            throw ValidationException::withMessages([
                $request->filled('recovery_code') ? 'recovery_code' : 'code' => __('The provided two-factor code was invalid.'),
            ]);
        }

        if ($request->filled('recovery_code')) {
            Audit::record('auth.recovery_code_used', null, [], $user);
        }

        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.id');
        $user = $id ? User::find($id) : null;

        return $user?->is_active && $user->hasEnabledTwoFactor() ? $user : null;
    }
}
