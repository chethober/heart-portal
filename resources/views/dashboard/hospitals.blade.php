<x-dashboard-layout :title="__('Hospitals')">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-3 py-2 text-start">{{ __('Hospital') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('City') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Staff') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Registered') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Status') }}</th>
                    <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($hospitals as $hospital)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $hospital->name }}<div class="text-xs text-gray-500">{{ $hospital->phone }}</div></td>
                        <td class="px-3 py-2">{{ $hospital->city }}</td>
                        <td class="px-3 py-2">{{ $hospital->users_count }}</td>
                        <td class="px-3 py-2">{{ $hospital->created_at->toDateString() }}</td>
                        <td class="px-3 py-2">{{ $hospital->status->label() }}</td>
                        <td class="px-3 py-2 text-end">
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
                    <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('No hospitals have registered yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $hospitals->links() }}</div>
</x-dashboard-layout>
