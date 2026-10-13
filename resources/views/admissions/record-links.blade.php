{{-- "Record a study" menu for one admission: the heart forms open linked to it. --}}
<details class="relative inline-block text-start">
    <summary class="cursor-pointer list-none rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Record a study') }}</summary>
    <div class="absolute end-0 z-10 mt-1 w-56 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
        @can('echo.create')
            <a href="{{ route('studies.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Echocardiography') }}</a>
        @endcan
        @foreach (App\Support\Forms::procedures(Auth::user(), 'create') as $key)
            <a href="{{ route('procedures.create', [$key, 'nationalID' => $admission->patient->national_id, 'admission' => $admission->id]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __(App\Support\Procedures::label($key)) }}</a>
        @endforeach
    </div>
</details>
