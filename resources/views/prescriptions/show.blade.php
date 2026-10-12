@php
    /**
     * A prescription, laid out to be printed. Like the clinical reports it is in English. A signed
     * prescription carries a QR code linking to its public verification page (no patient data).
     *
     * @var \App\Models\Prescription $prescription
     * @var string|null $qrCode  Inline SVG, once signed.
     */
    use App\Support\JalaliDate;

    $patient = $prescription->patient;
    $buttonPrimary = 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700';
    $buttonSecondary = 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50';
@endphp

<x-dashboard-layout title="Prescription">
    <div dir="ltr" lang="en">
        <div class="mb-6 flex flex-wrap items-center gap-3 print:hidden">
            <button type="button" onclick="window.print()" class="{{ $buttonPrimary }}">Print Prescription</button>
            <a href="{{ route('patients.index', ['nationalID' => $patient->national_id]) }}" class="{{ $buttonSecondary }}">Back to Patient</a>
            @if ($prescription->dischargeStudy)
                <a href="{{ route('procedures.show', ['discharge', $prescription->dischargeStudy]) }}" class="{{ $buttonSecondary }}">Discharge Summary</a>
            @endif
            @can('update', $prescription)
                <a href="{{ route('prescriptions.edit', $prescription) }}" class="{{ $buttonSecondary }}">Edit Draft</a>
            @endcan
            @can('sign', $prescription)
                <form method="POST" action="{{ route('prescriptions.sign', $prescription) }}" onsubmit="return confirm('Sign this prescription? It cannot be changed afterwards, only cancelled.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">Sign Prescription</button>
                </form>
            @endcan
            @can('delete', $prescription)
                <form method="POST" action="{{ route('prescriptions.destroy', $prescription) }}" onsubmit="return confirm('Delete this draft?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="{{ $buttonSecondary }} text-red-700">Delete Draft</button>
                </form>
            @endcan
            <span class="ms-auto">@include('prescriptions.partials.status-badge', ['status' => $prescription->status])</span>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800 print:hidden" role="alert">
                @foreach ($errors->all() as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        @endif

        @if ($prescription->isCancelled())
            <div class="mb-6 rounded-md border border-red-300 bg-red-50 p-3 text-red-900">
                <p class="font-semibold">CANCELLED — NOT VALID FOR DISPENSING</p>
                <p class="text-sm">Cancelled by {{ $prescription->canceller?->name }} on {{ JalaliDate::format($prescription->cancelled_at) }}: {{ $prescription->cancellation_reason }}</p>
            </div>
        @endif

        <header class="mb-6 border-b border-gray-200 pb-4">
            <div class="flex flex-wrap items-start gap-4">
                <div>
                    <p class="text-sm text-gray-500">{{ $prescription->hospital->name }}</p>
                    <h1 class="text-2xl font-semibold">{{ $prescription->discharge_study_id ? 'Discharge Prescription' : 'Prescription' }}</h1>
                    @if ($prescription->isDraft())
                        <p class="mt-1 font-semibold text-red-700">DRAFT — NOT SIGNED</p>
                    @endif
                </div>
                <p class="ms-auto text-sm">Rx No. <strong>{{ $prescription->id }}</strong></p>
            </div>
            <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                <div><dt class="inline text-gray-500">Patient:</dt> <dd class="inline font-medium">{{ $patient->first_name }} {{ $patient->last_name }}</dd></div>
                <div><dt class="inline text-gray-500">National ID:</dt> <dd class="inline">{{ $patient->national_id }}</dd></div>
                <div><dt class="inline text-gray-500">Date of birth:</dt> <dd class="inline">{{ $patient->dob }}</dd></div>
                <div><dt class="inline text-gray-500">Date:</dt> <dd class="inline">{{ $prescription->prescribed_on }}</dd></div>
                @if ($prescription->admission)
                    <div><dt class="inline text-gray-500">Admission:</dt> <dd class="inline">{{ $prescription->admission->ward }} {{ $prescription->admission->admitted_on }}</dd></div>
                @endif
            </dl>
        </header>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="py-2 pe-3 text-start">#</th>
                    <th class="px-3 py-2 text-start">Drug</th>
                    <th class="px-3 py-2 text-start">Directions</th>
                    <th class="px-3 py-2 text-start">Qty</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($prescription->items as $item)
                    <tr class="break-inside-avoid align-top">
                        <td class="py-2 pe-3">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 font-medium">{{ $item->drugLabel() }}</td>
                        <td class="px-3 py-2">
                            {{ $item->directions() }}
                            @if ($item->instructions)
                                <span class="block text-gray-600" dir="auto">{{ $item->instructions }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($prescription->notes)
            <p class="mt-4 whitespace-pre-line text-sm" dir="auto"><span class="text-gray-500">Notes:</span> {{ $prescription->notes }}</p>
        @endif

        <footer class="mt-8 flex flex-wrap items-end gap-6 border-t border-gray-200 pt-4 text-sm">
            <div>
                @if ($prescription->signer)
                    Prescribed and signed by <strong>{{ $prescription->signer->name }}</strong>
                    @if ($prescription->signer->medical_license_no) (License {{ $prescription->signer->medical_license_no }}) @endif
                    on {{ JalaliDate::format($prescription->signed_at) }}
                    @if ($prescription->signer->signature_path)
                        <img src="{{ route('users.signature', $prescription->signer) }}" alt="Signature" class="mt-2 h-16">
                    @endif
                @else
                    Drafted by {{ $prescription->author?->name }}
                @endif
            </div>
            @if ($qrCode)
                <div class="ms-auto text-center">
                    <div class="inline-block" aria-label="Verification QR code">{!! $qrCode !!}</div>
                    <p class="mt-1 text-xs text-gray-500">Scan to verify this prescription</p>
                </div>
            @endif
        </footer>

        @can('cancel', $prescription)
            <form method="POST" action="{{ route('prescriptions.cancel', $prescription) }}" class="mt-8 max-w-xl space-y-2 rounded-md border border-gray-200 p-4 print:hidden"
                  onsubmit="return confirm('Cancel this signed prescription? Pharmacies will see it as cancelled.');">
                @csrf
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">Reason for cancelling</span>
                    <input type="text" name="cancellation_reason" required minlength="5" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                </label>
                <button type="submit" class="{{ $buttonSecondary }} text-red-700">Cancel Prescription</button>
            </form>
        @endcan
    </div>
</x-dashboard-layout>
