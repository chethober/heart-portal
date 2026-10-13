<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionRequest;
use App\Models\Admission;
use App\Models\DischargeStudy;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\Prescription;
use App\Support\Audit;
use App\Support\JalaliDate;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Heart's e-prescription module: write a prescription for a patient (optionally for an admission
 * or as a discharge summary's discharge medications), sign it, print it with a QR code, cancel it.
 */
class PrescriptionController extends Controller
{
    /**
     * New prescription for ?patient= or, for discharge medications, ?discharge= (a discharge summary).
     */
    public function create(Request $request): View|RedirectResponse
    {
        $discharge = null;

        if ($request->filled('discharge')) {
            $discharge = DischargeStudy::findOrFail($request->integer('discharge'));

            // A discharge summary has one current discharge prescription, as heart's drugDischarge_ep.
            if ($existing = $this->activeDischargePrescription($discharge)) {
                return redirect()->route('prescriptions.show', $existing);
            }

            $patient = $discharge->patient;
        } else {
            $patient = Patient::findOrFail($request->integer('patient'));
        }

        $prescription = new Prescription([
            'patient_id' => $patient->id,
            'discharge_study_id' => $discharge?->id,
            'admission_id' => $discharge ? $discharge->admission_id : ($request->integer('admission') ?: null),
            'prescribed_on' => JalaliDate::format(now(), 'yyyy/MM/dd'),
        ]);

        return $this->form($prescription->setRelation('patient', $patient));
    }

    public function store(PrescriptionRequest $request): RedirectResponse
    {
        $prescription = DB::transaction(function () use ($request) {
            if ($request->filled('discharge_study_id')) {
                $discharge = DischargeStudy::find($request->integer('discharge_study_id'))
                    ?? throw ValidationException::withMessages(['discharge_study_id' => __('The discharge summary was not found.')]);

                if ($this->activeDischargePrescription($discharge)) {
                    throw ValidationException::withMessages(['discharge_study_id' => __('This discharge summary already has a prescription.')]);
                }

                $attributes = ['patient_id' => $discharge->patient_id, 'admission_id' => $discharge->admission_id, 'discharge_study_id' => $discharge->id];
            } else {
                $patient = Patient::find($request->integer('patient_id'))
                    ?? throw ValidationException::withMessages(['patient_id' => __('The patient was not found.')]);

                $attributes = ['patient_id' => $patient->id, 'admission_id' => $this->admissionId($request, $patient->id)];
            }

            $prescription = Prescription::create([
                ...$attributes,
                ...$request->safe()->only(['prescribed_on', 'notes']),
                'created_by' => $request->user()->id,
            ]);
            $prescription->items()->createMany($this->items($request));

            Audit::record('prescription.created', $prescription, ['items' => $prescription->items()->count()]);

            return $prescription;
        });

        return redirect()->route('prescriptions.show', $prescription)->with('status', __('Prescription saved.'));
    }

    public function show(Prescription $prescription): View
    {
        Gate::authorize('view', $prescription);

        $prescription->load(['patient', 'admission', 'dischargeStudy', 'items', 'author', 'signer', 'canceller', 'hospital']);
        Audit::record('prescription.viewed', $prescription);

        $url = $prescription->isDraft() ? null : $prescription->verificationUrl();

        return view('prescriptions.show', [
            'prescription' => $prescription,
            'qrCode' => $url ? $this->qrCodeSvg($url) : null,
        ]);
    }

    public function edit(Prescription $prescription): View
    {
        Gate::authorize('update', $prescription);

        return $this->form($prescription->load(['patient', 'items']));
    }

    /**
     * Saves a draft; its drugs are replaced by the submitted list, as heart's save did.
     */
    public function update(PrescriptionRequest $request, Prescription $prescription): RedirectResponse
    {
        DB::transaction(function () use ($request, $prescription) {
            $prescription->fill($request->safe()->only(['prescribed_on', 'notes']));

            if ($prescription->discharge_study_id === null) {
                $prescription->admission_id = $this->admissionId($request, $prescription->patient_id);
            }

            $changes = Audit::diff($prescription);
            $prescription->save();
            $prescription->items()->delete();
            $prescription->items()->createMany($this->items($request));

            Audit::record('prescription.updated', $prescription, ['fields' => $changes, 'items' => $prescription->items()->count()]);
        });

        return redirect()->route('prescriptions.show', $prescription)->with('status', __('Prescription saved.'));
    }

    public function sign(Request $request, Prescription $prescription): RedirectResponse
    {
        Gate::authorize('sign', $prescription);

        if (! $prescription->items()->exists()) {
            return back()->withErrors(['items' => __('Add at least one drug.')]);
        }

        DB::transaction(function () use ($request, $prescription) {
            $prescription->sign($request->user());
            Audit::record('prescription.signed', $prescription);
        });

        return redirect()->route('prescriptions.show', $prescription)->with('status', __('Prescription signed.'));
    }

    /**
     * Withdraws a signed prescription (heart's presc_cancel); it stays on record, marked cancelled.
     */
    public function cancel(Request $request, Prescription $prescription): RedirectResponse
    {
        Gate::authorize('cancel', $prescription);

        $reason = $request->validate(['cancellation_reason' => ['required', 'string', 'min:5', 'max:500']])['cancellation_reason'];

        DB::transaction(function () use ($request, $prescription, $reason) {
            $prescription->cancel($request->user(), $reason);
            Audit::record('prescription.cancelled', $prescription, ['reason' => $reason]);
        });

        return redirect()->route('prescriptions.show', $prescription)->with('status', __('Prescription cancelled.'));
    }

    public function destroy(Prescription $prescription): RedirectResponse
    {
        Gate::authorize('delete', $prescription);

        $prescription->delete();
        Audit::record('prescription.deleted', $prescription);

        return redirect()->route('patients.index', ['nationalID' => $prescription->patient->national_id])
            ->with('status', __('Draft deleted.'));
    }

    private function form(Prescription $prescription): View
    {
        $prescription->loadMissing('dischargeStudy');

        return view('prescriptions.form', [
            'prescription' => $prescription,
            'admissions' => Admission::where('patient_id', $prescription->patient_id)->latest('id')->get(),
        ]);
    }

    private function activeDischargePrescription(DischargeStudy $discharge): ?Prescription
    {
        return $discharge->prescriptions()->where('status', '!=', PrescriptionStatus::Cancelled->value)->first();
    }

    /**
     * The requested admission, which must be the patient's and in this hospital.
     */
    private function admissionId(PrescriptionRequest $request, int $patientId): ?int
    {
        if (! $request->filled('admission_id')) {
            return null;
        }

        $admission = Admission::find($request->integer('admission_id'));

        if ($admission?->patient_id !== $patientId) {
            throw ValidationException::withMessages(['admission_id' => __('The admission belongs to another patient.')]);
        }

        return $admission->id;
    }

    /**
     * Item rows to save. Catalog drugs are looked up in this hospital's catalog and their name,
     * form and strength copied onto the item; other drugs are kept as typed.
     *
     * @return list<array<string, mixed>>
     */
    private function items(PrescriptionRequest $request): array
    {
        $items = $request->validated('items');
        $drugs = Drug::whereKey(array_filter(array_column($items, 'drug_id')))->get()->keyBy('id');
        $rows = [];

        foreach ($items as $index => $item) {
            $drug = null;

            if (filled($item['drug_id'] ?? null)) {
                $drug = $drugs->get((int) $item['drug_id'])
                    ?? throw ValidationException::withMessages(["items.{$index}.drug_id" => __('The drug is not in the catalog.')]);
            }

            $rows[] = [
                'drug_id' => $drug?->id,
                'position' => $index + 1,
                'drug_name' => $drug ? $drug->generic_name : trim($item['drug_name']),
                'form' => $drug?->form,
                'strength' => $drug?->strength,
                ...array_map(fn ($value) => is_string($value) ? (trim($value) ?: null) : $value, [
                    'dose' => $item['dose'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'route' => $item['route'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                ]),
            ];
        }

        return $rows;
    }

    private function qrCodeSvg(string $url): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(128, 0), new SvgImageBackEnd)))->writeString($url);

        // Drop the XML declaration so the SVG can be inlined in HTML.
        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
