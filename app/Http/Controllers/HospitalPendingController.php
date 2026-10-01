<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HospitalPendingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $hospital = $request->user()->hospital;

        if ($hospital === null || $hospital->isActive()) {
            return redirect()->route('dashboard');
        }

        return view('hospital.pending', ['hospital' => $hospital]);
    }
}
