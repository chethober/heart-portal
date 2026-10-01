<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Http\Middleware\EnsureHospitalIsActive;
use App\Models\EchoStudy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard home: the hospital's study worklist. The other dashboard
 * sections (patient echos, staff, audit log, hospitals) have their own routes
 * under /dashboard and share its tabs.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('admin.hospitals.index');
        }

        // Links from before the sections had their own URLs.
        if ($request->query('tab') === 'patients') {
            return redirect()->route('patients.index', $request->only('nationalID'));
        }

        // The worklist shows patient data, so it needs the same membership checks
        // as the clinical routes (platform admins were redirected above).
        return app(EnsureHospitalIsActive::class)->handle($request, fn () => response($this->worklist($request)));
    }

    private function worklist(Request $request): View
    {
        if (! $request->user()->can('patients.view')) {
            return view('dashboard.studies');
        }

        $status = StudyStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('search', ''));

        $studies = EchoStudy::query()
            ->with(['patient', 'author'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('patient', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.studies', [
            'studies' => $studies,
            'status' => $status->value ?? '',
            'search' => $search,
            'counts' => EchoStudy::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
