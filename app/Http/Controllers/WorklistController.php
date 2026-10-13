<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\ProcedureStudy;
use App\Support\Forms;
use App\Support\Procedures;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The hospital's study worklist, filterable by status and patient name. Echo studies
 * are paged; drafts of heart's procedures are listed with them.
 */
class WorklistController extends Controller
{
    private const PROCEDURE_DRAFTS = 50;

    public function __invoke(Request $request): View
    {
        $status = StudyStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('search', ''));

        $searchPatient = fn (Builder $query) => $query->whereHas('patient', function (Builder $query) use ($search) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn (Builder $query) => $query
                ->where('first_name', 'ilike', $like)
                ->orWhere('last_name', 'ilike', $like));
        });

        // Each list holds only the forms this member may view (per-form permissions).
        $user = $request->user();
        $echo = Forms::allows($user, 'echo', 'view');

        $studies = EchoStudy::query()
            ->with(['patient', 'author'])
            ->unless($echo, fn (Builder $query) => $query->whereRaw('false'))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', $searchPatient)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.studies', [
            'studies' => $studies,
            'status' => $status->value ?? '',
            'search' => $search,
            'counts' => $echo ? EchoStudy::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect(),
            'procedureDrafts' => $status === null || $status === StudyStatus::Draft ? $this->procedureDrafts(Forms::procedures($user, 'view'), $search !== '' ? $searchPatient : null) : collect(),
        ]);
    }

    /**
     * Drafts of the procedures ported from heart, newest first (each knows its key as ::PROCEDURE).
     *
     * @param  list<string>  $procedures  Keys of the procedures to list.
     * @param  (Closure(Builder<covariant ProcedureStudy>): mixed)|null  $search
     * @return Collection<int, ProcedureStudy>
     */
    private function procedureDrafts(array $procedures, ?Closure $search): Collection
    {
        $drafts = new Collection;

        foreach ($procedures as $key) {
            $drafts = $drafts->concat(Procedures::model($key)::query()
                ->with(['patient', 'author'])
                ->where('status', StudyStatus::Draft)
                ->when($search, $search)
                ->latest()
                ->limit(self::PROCEDURE_DRAFTS)
                ->get());
        }

        return $drafts->sortByDesc('created_at')->take(self::PROCEDURE_DRAFTS)->values();
    }
}
