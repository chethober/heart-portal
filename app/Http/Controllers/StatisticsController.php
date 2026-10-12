<?php

namespace App\Http\Controllers;

use App\Support\RegistryStatistics;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The hospital's registry statistics: studies per form, monthly volume and the distribution of key
 * findings (ported from the charts on heart's home page).
 */
class StatisticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $months = in_array($request->integer('months'), RegistryStatistics::PERIODS, true) ? $request->integer('months') : 12;
        $statistics = new RegistryStatistics($months);

        return view('dashboard.statistics', [
            'months' => $months,
            'totals' => $statistics->totals(),
            'monthly' => $statistics->monthly(),
            'distributions' => $statistics->distributions(),
        ]);
    }
}
