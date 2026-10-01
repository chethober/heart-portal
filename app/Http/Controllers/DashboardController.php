<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('admin.hospitals.index');
        }

        $hospital = $request->user()->hospital;

        if (! $hospital->isActive()) {
            return redirect()->route('hospital.pending');
        }

        return view('dashboard', ['hospital' => $hospital]);
    }
}
