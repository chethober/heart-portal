@php
    /**
     * @var int $months
     * @var list<array{key: string, label: string, total: int, signed: int, drafts: int, patients: int}> $totals
     * @var array{months: list<string>, series: array<string, array<string, int>>, max: int} $monthly
     * @var list<array{procedure: string, field: string, label: string, total: int, rows: list<array{label: string, count: int}>}> $distributions
     */
    use App\Support\RegistryStatistics;

    // One colour per form, in the order of $totals.
    $palette = ['#4f46e5', '#0891b2', '#16a34a', '#ca8a04', '#dc2626', '#9333ea', '#64748b'];
    $colors = array_combine(array_column($totals, 'key'), array_slice($palette, 0, count($totals)));
    $monthTotals = array_map(fn (string $month) => array_sum(array_column($monthly['series'], $month)), $monthly['months']);
@endphp

<x-dashboard-layout :title="__('Statistics')">
    <section class="mb-8">
        <h3 class="mb-3 text-lg font-semibold">{{ __('Studies per form') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ __('Form') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('Studies') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('Signed') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('Drafts') }}</th>
                        <th class="px-3 py-2 text-end">{{ __('Patients') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums">
                    @foreach ($totals as $row)
                        <tr>
                            <td class="px-3 py-2">
                                <span class="me-2 inline-block h-3 w-3 rounded-sm align-middle" style="background: {{ $colors[$row['key']] }}"></span>{{ $row['label'] }}
                            </td>
                            <td class="px-3 py-2 text-end font-semibold">{{ $row['total'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['signed'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['drafts'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['patients'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">{{ __('Studies per month') }}</h3>
            <form method="GET" action="{{ route('statistics') }}" class="flex items-center gap-2 text-sm">
                <label for="months" class="text-gray-600">{{ __('Period') }}</label>
                <select id="months" name="months" class="border-gray-300 rounded-md text-sm" onchange="this.form.submit()">
                    @foreach (RegistryStatistics::PERIODS as $period)
                        <option value="{{ $period }}" @selected($period === $months)>{{ __('Last :count months', ['count' => $period]) }}</option>
                    @endforeach
                </select>
                <noscript><x-primary-button>{{ __('Filter') }}</x-primary-button></noscript>
            </form>
        </div>

        @if ($monthly['max'] === 0)
            <p class="py-6 text-center text-gray-500">{{ __('No studies in this period.') }}</p>
        @else
            {{-- Stacked columns; the table below carries the same numbers for screen readers. --}}
            <div class="flex h-48 items-end gap-1 border-b border-gray-300" aria-hidden="true" dir="ltr">
                @foreach ($monthly['months'] as $i => $month)
                    <div class="flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $month }}: {{ $monthTotals[$i] }}">
                        @if ($monthTotals[$i] > 0)
                            <span class="text-center text-[10px] text-gray-600 tabular-nums">{{ $monthTotals[$i] }}</span>
                        @endif
                        @foreach (array_reverse($monthly['series'], true) as $key => $counts)
                            @if ($counts[$month] > 0)
                                <div style="height: {{ round($counts[$month] / $monthly['max'] * 100, 2) }}%; background: {{ $colors[$key] }}"></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex gap-1 text-[10px] text-gray-500" aria-hidden="true" dir="ltr">
                @foreach ($monthly['months'] as $month)
                    <span class="min-w-0 flex-1 truncate text-center">{{ $month }}</span>
                @endforeach
            </div>
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600" aria-hidden="true">
                @foreach ($totals as $row)
                    <li><span class="me-1 inline-block h-2.5 w-2.5 rounded-sm align-middle" style="background: {{ $colors[$row['key']] }}"></span>{{ $row['label'] }}</li>
                @endforeach
            </ul>

            <details class="mt-3 text-sm">
                <summary class="cursor-pointer text-gray-600">{{ __('Show as a table') }}</summary>
                <div class="mt-2 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs tabular-nums">
                        <thead class="font-semibold text-gray-500">
                            <tr>
                                <th class="px-2 py-1 text-start">{{ __('Month') }}</th>
                                @foreach ($totals as $row)
                                    <th class="px-2 py-1 text-end">{{ $row['label'] }}</th>
                                @endforeach
                                <th class="px-2 py-1 text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($monthly['months'] as $i => $month)
                                <tr>
                                    <td class="px-2 py-1" dir="ltr">{{ $month }}</td>
                                    @foreach ($totals as $row)
                                        <td class="px-2 py-1 text-end">{{ $monthly['series'][$row['key']][$month] }}</td>
                                    @endforeach
                                    <td class="px-2 py-1 text-end font-semibold">{{ $monthTotals[$i] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </section>

    <section>
        <h3 class="mb-3 text-lg font-semibold">{{ __('Findings') }}</h3>
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($distributions as $chart)
                <figure class="rounded-md border border-gray-200 p-4">
                    <figcaption class="mb-2 text-sm">
                        <span class="text-gray-500">{{ $chart['procedure'] }}</span> · <strong dir="auto">{{ $chart['label'] }}</strong>
                    </figcaption>
                    @if ($chart['total'] === 0)
                        <p class="text-sm text-gray-500">{{ __('No studies recorded yet.') }}</p>
                    @else
                        @php($max = max(array_column($chart['rows'], 'count')))
                        <dl class="space-y-1 text-sm">
                            @foreach ($chart['rows'] as $row)
                                <div class="grid grid-cols-[minmax(0,10rem)_1fr_auto] items-center gap-2">
                                    <dt class="truncate text-gray-700" dir="auto" title="{{ $row['label'] }}">{{ $row['label'] }}</dt>
                                    <dd class="h-3 rounded-sm bg-gray-100" aria-hidden="true">
                                        <div class="h-full rounded-sm bg-indigo-500" style="width: {{ round($row['count'] / $max * 100, 2) }}%"></div>
                                    </dd>
                                    <dd class="w-16 text-end tabular-nums text-gray-600">{{ $row['count'] }} <span class="text-gray-400">({{ round($row['count'] / $chart['total'] * 100) }}%)</span></dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
</x-dashboard-layout>
