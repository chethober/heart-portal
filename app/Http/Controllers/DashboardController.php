<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Http\Middleware\EnsureHospitalIsActive;
use App\Models\EchoStudy;
use App\Models\Patient;
use App\Support\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The hospital dashboard: a "studies" tab (the hospital's worklist) and a
 * "patients" tab (search one patient's echo reports by national ID).
 */
class DashboardController extends Controller
{
    public const TABS = ['studies', 'patients'];

    public function __invoke(Request $request): Response
    {
        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('admin.hospitals.index');
        }

        // The dashboard shows patient data, so it needs the same membership checks
        // as the clinical routes (platform admins were redirected above).
        return app(EnsureHospitalIsActive::class)->handle($request, fn () => response($this->render($request)));
    }

    private function render(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'studies';
        $data = ['hospital' => $user->hospital, 'tab' => $tab];

        if (! $user->can('patients.view')) {
            return view('dashboard', $data);
        }

        return view('dashboard', [
            ...$data,
            ...($tab === 'studies' ? $this->studies($request) : $this->patients($request)),
        ]);
    }

    /**
     * @return array{studies: LengthAwarePaginator<int, EchoStudy>, status: string, search: string}
     */
    private function studies(Request $request): array
    {
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

        return ['studies' => $studies, 'status' => $status->value ?? '', 'search' => $search];
    }

    /**
     * Search by national ID and list that patient's echo studies.
     *
     * @return array{nationalID: string, patient: Patient|null, echoReports: Collection<int, EchoStudy>}
     */
    private function patients(Request $request): array
    {
        $nationalID = trim((string) $request->query('nationalID', ''));
        $patient = null;
        $echoReports = null;

        if ($nationalID !== '') {
            $patient = Patient::whereNationalId($nationalID)->first();

            // The searched ID itself is not logged; a miss is recorded without it.
            Audit::record($patient ? 'patient.viewed' : 'patient.search_no_match', $patient);

            $echoReports = $patient?->studies()->with('author')->latest()->get()
                ->each(fn (EchoStudy $study) => $study->setRelation('patient', $patient));
        }

        return ['nationalID' => $nationalID, 'patient' => $patient, 'echoReports' => $echoReports ?? new Collection];
    }
}
