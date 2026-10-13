@php
    /**
     * The drug catalog (heart's drug list): the shared catalog plus the hospital's own drugs,
     * which hospital admins add and withdraw here.
     *
     * @var string $search
     * @var \Illuminate\Pagination\LengthAwarePaginator<int, \App\Models\Drug> $drugs
     */
    use App\Support\Prescribing;

    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $manage = auth()->user()->can('hospital.settings');
@endphp

<x-dashboard-layout :title="__('Drug catalog')">
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <h2 class="text-xl font-semibold">{{ __('Drug catalog') }}</h2>
        <form method="GET" action="{{ route('drugs.index') }}" class="ms-auto flex gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search drugs') }}" class="rounded-md border-gray-300 text-sm shadow-sm">
            <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold uppercase text-white">{{ __('Search') }}</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-3 py-2 text-start">{{ __('Drug') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Local name') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Usual directions') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Source') }}</th>
                    @if ($manage)
                        <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($drugs as $drug)
                    <tr @class(['text-gray-400' => ! $drug->is_active])>
                        <td class="px-3 py-2 font-medium" dir="ltr">{{ $drug->displayName() }}</td>
                        <td class="px-3 py-2" dir="auto">{{ $drug->local_name }}</td>
                        <td class="px-3 py-2" dir="ltr">{{ trim($drug->default_dose.' '.$drug->default_unit.' '.Prescribing::routeLabel($drug->default_route)) }} {{ Prescribing::frequencyLabel($drug->default_frequency) }}</td>
                        <td class="px-3 py-2">{{ $drug->isShared() ? __('Shared catalog') : __('This hospital') }}@unless ($drug->is_active) · {{ __('Withdrawn') }}@endunless</td>
                        @if ($manage)
                            <td class="px-3 py-2 text-end">
                                @unless ($drug->isShared())
                                    <form method="POST" action="{{ route('drugs.update', $drug) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $drug->is_active ? 0 : 1 }}">
                                        <button type="submit" class="text-sm font-medium text-indigo-600 underline">{{ $drug->is_active ? __('Withdraw') : __('Restore') }}</button>
                                    </form>
                                @endunless
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">{{ __('No drugs found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $drugs->links() }}</div>

    @if ($manage)
        <form method="POST" action="{{ route('drugs.store') }}" class="mt-8 rounded-lg border border-gray-200 p-4">
            @csrf
            <h3 class="font-semibold">{{ __('Add a drug for this hospital') }}</h3>
            @if ($errors->drug->any())
                <ul class="mt-2 list-disc ps-5 text-sm text-red-700">
                    @foreach ($errors->drug->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-3 grid gap-4 sm:grid-cols-4" dir="ltr">
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">{{ __('Generic name') }}</span>
                    <input type="text" name="generic_name" value="{{ old('generic_name') }}" required class="{{ $input }}"></label>
                <label class="block text-sm sm:col-span-2"><span class="font-medium text-gray-700">{{ __('Local name') }}</span>
                    <input type="text" name="local_name" dir="auto" value="{{ old('local_name') }}" class="{{ $input }}"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Form') }}</span>
                    <select name="form" required class="{{ $input }}">
                        @foreach (Prescribing::FORMS as $form)
                            <option value="{{ $form }}" @selected(old('form') === $form)>{{ $form }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Strength') }}</span>
                    <input type="text" name="strength" value="{{ old('strength') }}" placeholder="40 mg" class="{{ $input }}"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Usual dose') }}</span>
                    <input type="text" name="default_dose" value="{{ old('default_dose') }}" class="{{ $input }}"></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Unit') }}</span>
                    <select name="default_unit" class="{{ $input }}"><option value=""></option>
                        @foreach (Prescribing::UNITS as $unit)
                            <option value="{{ $unit }}" @selected(old('default_unit') === $unit)>{{ $unit }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Route') }}</span>
                    <select name="default_route" class="{{ $input }}"><option value=""></option>
                        @foreach (Prescribing::ROUTES as $code => $label)
                            <option value="{{ $code }}" @selected(old('default_route') === $code)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                <label class="block text-sm"><span class="font-medium text-gray-700">{{ __('Frequency') }}</span>
                    <select name="default_frequency" class="{{ $input }}"><option value=""></option>
                        @foreach (Prescribing::FREQUENCIES as $code => $label)
                            <option value="{{ $code }}" @selected(old('default_frequency') === $code)>{{ $label }}</option>
                        @endforeach
                    </select></label>
            </div>
            <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Add drug') }}</button>
        </form>
    @endif
</x-dashboard-layout>
