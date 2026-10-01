<?php

namespace App\Http\Controllers;

use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The hospital's study worklist, filterable by status and patient name.
 */
class WorklistController extends Controller
{
    public function __invoke(Request $request): View
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

        return view('dashboard.studies', [
            'studies' => $studies,
            'status' => $status->value ?? '',
            'search' => $search,
            'counts' => EchoStudy::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
