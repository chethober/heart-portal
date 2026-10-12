<x-dashboard-layout :title="__('Staff')">
    <div class="space-y-8">
        <x-input-error :messages="$errors->get('staff')" />

        <section>
            <h3 class="font-semibold text-gray-900">{{ __('Invite a staff member') }}</h3>
            <form method="POST" action="{{ route('staff.invitations.store') }}" class="mt-3 flex flex-col sm:flex-row gap-3 sm:items-end">
                @csrf
                <div class="flex-1">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                </div>
                <div>
                    <x-input-label for="role" :value="__('Role')" />
                    <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', 'technician') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>{{ __('Send invitation') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </section>

        @if ($invitations->isNotEmpty())
            <section>
                <h3 class="font-semibold text-gray-900">{{ __('Pending invitations') }}</h3>
                <ul class="mt-3 divide-y divide-gray-100 text-sm">
                    @foreach ($invitations as $invitation)
                        <li class="py-2 flex items-center justify-between gap-4">
                            <span>{{ $invitation->email }} — {{ $invitation->role->label() }} <span class="text-gray-500">({{ __('expires :date', ['date' => $invitation->expires_at->diffForHumans()]) }})</span></span>
                            <form method="POST" action="{{ route('staff.invitations.destroy', $invitation) }}">
                                @csrf
                                @method('DELETE')
                                <x-secondary-button type="submit">{{ __('Revoke') }}</x-secondary-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <h3 class="font-semibold text-gray-900">{{ __('Team') }}</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ __('Name') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('Email') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('Role') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('Active') }}</th>
                            <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($staff as $member)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $member->name }}</td>
                                <td class="px-3 py-2">{{ $member->email }}</td>
                                <td class="px-3 py-2">
                                    <select name="role" form="staff-{{ $member->id }}" aria-label="{{ __('Role') }}" class="border-gray-300 rounded-md text-sm">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($member->primaryRole() === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    {{-- Hidden fallback must precede the checkbox in document order so a checked box wins. --}}
                                    <input type="hidden" name="is_active" value="0" form="staff-{{ $member->id }}">
                                    <input type="checkbox" name="is_active" value="1" form="staff-{{ $member->id }}" aria-label="{{ __('Active') }}" class="rounded border-gray-300" @checked($member->is_active)>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <form id="staff-{{ $member->id }}" method="POST" action="{{ route('staff.update', $member) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-secondary-button type="submit">{{ __('Save') }}</x-secondary-button>
                                    </form>
                                    @if ($member->hasEnabledTwoFactor() && ! $member->is(Auth::user()))
                                        <form method="POST" action="{{ route('staff.two-factor.reset', $member) }}" class="mt-1" onsubmit="return confirm(@js(__('Reset two-factor login for this user? Verify their identity first.')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-gray-500 underline">{{ __('Reset 2FA') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-dashboard-layout>
