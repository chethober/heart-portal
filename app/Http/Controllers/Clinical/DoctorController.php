<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\EchoStudy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Suggestions for the form's "Doctor" field: the hospital's doctor accounts
     * followed by names recently typed on studies.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $accounts = $request->user()->hospital->users()
            ->role(Role::Doctor->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        $recent = EchoStudy::query()
            ->whereNotNull('doctor')
            ->where('doctor', '!=', '')
            ->groupBy('doctor')
            ->orderByRaw('max(id) desc')
            ->limit(15)
            ->pluck('doctor');

        return response()->json($accounts->concat($recent)->unique()->values());
    }
}
