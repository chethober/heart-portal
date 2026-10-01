<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HospitalStatus;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function index(): View
    {
        $hospitals = Hospital::query()
            ->withCount('users')
            ->orderByRaw("case status when 'pending' then 0 when 'active' then 1 else 2 end")
            ->latest()
            ->paginate(25);

        return view('admin.hospitals.index', ['hospitals' => $hospitals]);
    }

    public function updateStatus(Request $request, Hospital $hospital): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(HospitalStatus::class)],
        ]);

        $status = HospitalStatus::from($validated['status']);

        $hospital->fill([
            'status' => $status,
            'approved_at' => $status === HospitalStatus::Active ? ($hospital->approved_at ?? now()) : $hospital->approved_at,
        ]);
        $changes = Audit::diff($hospital);
        $hospital->save();
        Audit::record('hospital.status_changed', $hospital, $changes);

        return back()->with('status', __(':name is now :status.', ['name' => $hospital->name, 'status' => $status->label()]));
    }
}
