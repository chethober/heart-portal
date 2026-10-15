@php
    use App\Enums\StudyStatus;
    use App\Support\JalaliDate;
@endphp

<x-dashboard-layout :title="__('Worklist')">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        {{-- Status filter: one click, with how many studies each status holds. --}}
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Status') }}">
            @php($filters = ['' => __('All')] + collect(StudyStatus::cases())->mapWithKeys(fn ($case) => [$case->value => __($case->label())])->all())
            @foreach ($filters as $value => $label)
                @php($count = $value === '' ? $counts->sum() : ($counts[$value] ?? 0))
                <a href="{{ route('worklist', array_filter(['status' => $value, 'search' => $search])) }}"
                   @if ($status === $value) aria-current="true" @endif
                   class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm {{ $status === $value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }} <span class="text-xs font-semibold">{{ $count }}</span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('worklist') }}" class="flex flex-wrap items-end gap-2" role="search">
            @if ($status !== '')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div>
                <x-input-label for="search" :value="__('Patient name')" class="sr-only" />
                <x-text-input id="search" name="search" type="search" class="block w-56" :value="$search" :placeholder="__('Patient name')" />
            </div>
            <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
            @if ($status !== '' || $search !== '')
                <a href="{{ route('worklist') }}" class="px-2 py-2 text-sm text-gray-500 underline hover:text-gray-700">{{ __('Clear filters') }}</a>
            @endif
        </form>
    </div>

    @if ($studies->isEmpty() && $procedureDrafts->isEmpty())
        <p class="py-8 text-center text-gray-500">{{ __('No studies found.') }}</p>
    @elseif ($studies->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2 text-start">{{ __('Date') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Patient') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Status') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Recorded by') }}</th>
                        <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($studies as $study)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2">{{ JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</td>
                            <td class="px-3 py-2">{{ $study->patient?->first_name }} {{ $study->patient?->last_name }}</td>
                            <td class="px-3 py-2">@include('dashboard.status-badge', ['status' => $study->status])</td>
                            <td class="px-3 py-2">{{ $study->author?->name }}</td>
                            <td class="px-3 py-2">@include('dashboard.study-actions', ['study' => $study, 'tab' => 'studies'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $studies->links() }}</div>
    @endif

    @if ($procedureDrafts->isNotEmpty())
        {{-- Drafts of the procedures ported from heart (angiography, ...). --}}
        <h3 class="mt-8 mb-2 text-lg font-semibold">{{ __('Procedure drafts') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2 text-start">{{ __('Date') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Study') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Patient') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Recorded by') }}</th>
                        <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($procedureDrafts as $draft)
                        @php($key = $draft::PROCEDURE)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2">{{ $draft->performedOn() ?? JalaliDate::format($draft->created_at, 'yyyy/MM/dd') }}</td>
                            <td class="px-3 py-2">{{ __(App\Support\Procedures::label($key)) }}</td>
                            <td class="px-3 py-2">{{ $draft->patient?->first_name }} {{ $draft->patient?->last_name }}</td>
                            <td class="px-3 py-2">{{ $draft->author?->name }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('procedures.show', [$key, $draft]) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('View report') }}</a>
                                    @can('update', $draft)
                                        <a href="{{ route('procedures.edit', [$key, $draft]) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('finalize', $draft)
                                        @php($step = $draft->nextSignoff())
                                        <form method="POST" action="{{ route('procedures.finalize', [$key, $draft]) }}" onsubmit="return confirm(@js($step ? __('Sign this report as :step?', ['step' => __($draft::SIGNOFFS[$step])]) : __('Sign and finalize this report? It can only be changed by amendment afterwards.')))">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-600">{{ $draft::SIGNOFFS ? __('Sign as :step', ['step' => __($draft->nextSignoffLabel())]) : __('Sign') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-dashboard-layout>
