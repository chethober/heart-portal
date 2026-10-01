<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for every hospital-facing route: the user must be an active member
 * of an approved hospital. Platform admins have no hospital and never see
 * patient data.
 */
class EnsureHospitalIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => __('Your account has been deactivated.')]);
        }

        if ($user->hospital_id === null) {
            abort(403);
        }

        if (! $user->hospital->isActive()) {
            return redirect()->route('hospital.pending');
        }

        return $next($request);
    }
}
