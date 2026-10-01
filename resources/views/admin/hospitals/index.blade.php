<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Hospitals') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-auth-session-status :status="session('status')" />

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-start text-gray-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Hospital') }}</th>
                            <th class="px-4 py-3">{{ __('City') }}</th>
                            <th class="px-4 py-3">{{ __('Staff') }}</th>
                            <th class="px-4 py-3">{{ __('Registered') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($hospitals as $hospital)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $hospital->name }}<div class="text-xs text-gray-500">{{ $hospital->phone }}</div></td>
                                <td class="px-4 py-3">{{ $hospital->city }}</td>
                                <td class="px-4 py-3">{{ $hospital->users_count }}</td>
                                <td class="px-4 py-3">{{ $hospital->created_at->toDateString() }}</td>
                                <td class="px-4 py-3">{{ $hospital->status->label() }}</td>
                                <td class="px-4 py-3 text-end">
                                    <form method="POST" action="{{ route('admin.hospitals.status', $hospital) }}">
                                        @csrf
                                        @method('PATCH')
                                        @if ($hospital->isActive())
                                            <input type="hidden" name="status" value="suspended">
                                            <x-danger-button>{{ __('Suspend') }}</x-danger-button>
                                        @else
                                            <input type="hidden" name="status" value="active">
                                            <x-primary-button>{{ $hospital->approved_at ? __('Reactivate') : __('Approve') }}</x-primary-button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">{{ __('No hospitals have registered yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $hospitals->links() }}
        </div>
    </div>
</x-app-layout>
