{{-- A patient's admissions, on their record page. --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead>
            <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                <th class="px-3 py-2 text-start">{{ __('Ward') }}</th>
                <th class="px-3 py-2 text-start">{{ __('Admitted') }}</th>
                <th class="px-3 py-2 text-start">{{ __('Discharged') }}</th>
                <th class="px-3 py-2 text-start">{{ __('Length of stay') }}</th>
                <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($admissions as $admission)
                @php($stay = $admission->lengthOfStay())
                <tr>
                    <td class="px-3 py-2">{{ $admission->wardName() ?? '—' }}</td>
                    <td class="whitespace-nowrap px-3 py-2">{{ $admission->admitted_on }} {{ $admission->admitted_at }}</td>
                    <td class="whitespace-nowrap px-3 py-2">
                        @if ($admission->isDischarged())
                            {{ $admission->discharged_on }} {{ $admission->discharged_at }}
                        @else
                            <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('Admitted') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-2">{{ $stay !== null ? __(':count days', ['count' => $stay]) : '—' }}</td>
                    <td class="px-3 py-2 text-end">
                        <a href="{{ route('admissions.show', [$admission->patient_id, $admission]) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('Open') }}</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
