<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Patient echo search now lives on the dashboard's "patients" tab.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard', ['tab' => 'patients', ...$request->only('nationalID')]);
    }
}
