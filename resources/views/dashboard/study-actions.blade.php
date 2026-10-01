{{-- Row actions for one study, shared by both dashboard tabs. --}}
<div class="flex flex-wrap items-center justify-end gap-2">
    <a href="{{ route('studies.show', $study) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('View report') }}</a>
    @can('update', $study)
        <a href="{{ route('studies.edit', $study) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ $study->isSigned() ? __('Amend') : __('Edit') }}</a>
    @endcan
    @can('finalize', $study)
        <form method="POST" action="{{ route('studies.finalize', $study) }}" onsubmit="return confirm(@js(__('Sign and finalize this report? It can only be changed by amendment afterwards.')))">
            @csrf
            <button type="submit" class="rounded-md bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-600">{{ __('Sign') }}</button>
        </form>
    @endcan
    @can('delete', $study)
        <form method="POST" action="{{ route('studies.destroy', $study) }}" onsubmit="return confirm(@js(__('Are you sure you want to delete this draft?')))">
            @csrf
            @method('DELETE')
            <input type="hidden" name="tab" value="{{ $tab }}">
            <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500">{{ __('Delete') }}</button>
        </form>
    @endcan
</div>
