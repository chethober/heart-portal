<x-dashboard-layout :title="__('Wards')">
    <div class="space-y-8">
        <section>
            <h3 class="font-semibold text-gray-900">{{ __('Add a ward') }}</h3>
            <form method="POST" action="{{ route('wards.store') }}" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <x-input-label for="ward-name" :value="__('Ward name')" />
                    <x-text-input id="ward-name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" maxlength="100" required />
                </div>
                <x-primary-button>{{ __('Add ward') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </section>

        <section>
            <h3 class="font-semibold text-gray-900">{{ __('Wards') }}</h3>
            @if ($wards->isEmpty())
                <p class="mt-3 text-sm text-gray-500">{{ __('No wards yet. Add the wards patients are admitted to.') }}</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-start">{{ __('Name') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('Current patients') }}</th>
                                <th class="px-3 py-2 text-start">{{ __('Active') }}</th>
                                <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($wards as $ward)
                                <tr @class(['text-gray-400' => ! $ward->is_active])>
                                    <td class="px-3 py-2">
                                        <input type="text" name="name" form="ward-{{ $ward->id }}" value="{{ $ward->name }}" maxlength="100" required aria-label="{{ __('Ward name') }}" class="w-full rounded-md border-gray-300 text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('ward-patients', ['ward' => $ward->id]) }}" class="text-indigo-600 underline hover:text-indigo-500">{{ $ward->current_count }}</a>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="is_active" value="0" form="ward-{{ $ward->id }}">
                                        <input type="checkbox" name="is_active" value="1" form="ward-{{ $ward->id }}" aria-label="{{ __('Active') }}" class="rounded border-gray-300" @checked($ward->is_active)>
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        <form id="ward-{{ $ward->id }}" method="POST" action="{{ route('wards.update', $ward) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-secondary-button type="submit">{{ __('Save') }}</x-secondary-button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ __('Inactive wards are no longer offered for new admissions; past admissions keep them.') }}</p>
            @endif
        </section>
    </div>
</x-dashboard-layout>
