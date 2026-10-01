@php
    use App\Enums\StudyStatus;
    use App\Support\JalaliDate;
@endphp

<x-dashboard-layout :title="__('Dashboard')">
    @cannot('patients.view')
        <p>{{ __('Signed in as :role.', ['role' => Auth::user()->primaryRole()?->label() ?? __('member')]) }}</p>
    @else
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            {{-- Status filter: one click, with how many studies each status holds. --}}
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Status') }}">
                @php($filters = ['' => __('All')] + collect(StudyStatus::cases())->mapWithKeys(fn ($case) => [$case->value => __($case->label())])->all())
                @foreach ($filters as $value => $label)
                    @php($count = $value === '' ? $counts->sum() : ($counts[$value] ?? 0))
                    <a href="{{ route('dashboard', array_filter(['status' => $value, 'search' => $search])) }}"
                       @if ($status === $value) aria-current="true" @endif
                       class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm {{ $status === $value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                        {{ $label }} <span class="text-xs font-semibold">{{ $count }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2" role="search">
                @if ($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div>
                    <x-input-label for="search" :value="__('Patient name')" class="sr-only" />
                    <x-text-input id="search" name="search" type="search" class="block w-56" :value="$search" :placeholder="__('Patient name')" />
                </div>
                <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
                @if ($status !== '' || $search !== '')
                    <a href="{{ route('dashboard') }}" class="px-2 py-2 text-sm text-gray-500 underline hover:text-gray-700">{{ __('Clear filters') }}</a>
                @endif
            </form>
        </div>

        @if ($studies->isEmpty())
            <p class="py-8 text-center text-gray-500">{{ __('No studies found.') }}</p>
        @else
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
    @endcannot
</x-dashboard-layout>
