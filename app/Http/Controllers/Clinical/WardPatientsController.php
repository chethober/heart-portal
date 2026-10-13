<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Patients currently admitted (no discharge date), by ward: heart's home page.
 */
class WardPatientsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $wards = Ward::query()->active()->orderBy('name')->get();
        $wardId = $request->integer('ward') ?: null;
        $ward = $wardId ? Ward::find($wardId) : null;

        $admissions = Admission::query()
            ->current()
            ->with(['patient', 'wardRecord'])
            ->whereHas('patient')
            ->when($wardId, fn ($query) => $query->where('ward_id', $wardId))
            ->orderBy('admitted_on')
            ->orderBy('id')
            ->get();

        return view('admissions.ward-patients', [
            'wards' => $wards,
            'ward' => $ward,
            'wardId' => $wardId,
            'admissions' => $admissions,
        ]);
    }
}
