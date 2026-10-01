<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When two-factor is enforced, send users who have not enrolled yet to the
 * profile page to set it up before they can use anything else.
 */
class EnsureTwoFactorEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('auth.two_factor.enforced') && ! $request->user()->hasEnabledTwoFactor()) {
            if ($request->expectsJson()) {
                abort(403, __('Two-factor authentication must be enabled.'));
            }

            return redirect()->to(route('profile.edit').'#two-factor')->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
