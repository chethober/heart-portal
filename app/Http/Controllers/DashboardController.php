<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureHospitalIsActive;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard home: the echo form for staff who record studies. Everyone else
 * lands on the first section they may open (worklist or hospitals); the sections
 * themselves are linked from the top navigation.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.hospitals.index');
        }

        // Everything from here shows or links to patient data, so it needs the same membership
        // checks as the clinical routes (platform admins were redirected above).
        return app(EnsureHospitalIsActive::class)->handle($request, function () use ($request, $user) {
            // Links from before the sections had their own URLs.
            if ($request->query('tab') === 'patients') {
                return redirect()->route('patients.index', $request->only('nationalID'));
            }
            if ($request->hasAny(['status', 'search']) || ! $user->can('studies.create')) {
                return redirect()->route('worklist', $request->only(['status', 'search', 'page']));
            }

            return response(view('studies.form'));
        });
    }
}
