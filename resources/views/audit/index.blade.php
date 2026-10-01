<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Audit log') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <x-input-label for="action" :value="__('Action')" />
                    <select id="action" name="action" class="mt-1 border-gray-300 rounded-md text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="user_id" :value="__('User')" />
                    <select id="user_id" name="user_id" class="mt-1 border-gray-300 rounded-md text-sm">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" @selected((int) ($filters['user_id'] ?? 0) === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 text-sm" :value="$filters['from'] ?? ''" />
                </div>
                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 text-sm" :value="$filters['to'] ?? ''" />
                </div>
                <x-primary-button>{{ __('Filter') }}</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-start text-gray-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Time') }}</th>
                            <th class="px-4 py-3">{{ __('User') }}</th>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                            <th class="px-4 py-3">{{ __('Subject') }}</th>
                            <th class="px-4 py-3">{{ __('Details') }}</th>
                            <th class="px-4 py-3">{{ __('IP') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 align-top">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap" title="{{ $log->created_at->toIso8601String() }}">{{ \App\Support\JalaliDate::format($log->created_at, 'yyyy/MM/dd HH:mm:ss') }}</td>
                                <td class="px-4 py-2">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $log->action }}</td>
                                <td class="px-4 py-2">
                                    @if ($log->subject_type === 'echo_study')
                                        <a class="underline" href="{{ route('studies.show', $log->subject_id) }}">{{ __('Study #:id', ['id' => $log->subject_id]) }}</a>
                                    @elseif ($log->subject_type)
                                        {{ $log->subject_type }} #{{ $log->subject_id }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if ($log->changes)
                                        <details>
                                            <summary class="cursor-pointer text-gray-600">{{ __('Show') }}</summary>
                                            <pre class="mt-1 text-xs whitespace-pre-wrap max-w-xl">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('No entries.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
