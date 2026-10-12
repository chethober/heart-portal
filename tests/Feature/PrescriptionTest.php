<?php

use App\Enums\PrescriptionStatus;
use App\Enums\Role;
use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Drug;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\CurrentHospital;
use App\Support\Procedures;
use Database\Seeders\DrugCatalogSeeder;

beforeEach(function () {
    $this->seed(DrugCatalogSeeder::class);
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr. Prescriber']);
    $this->patient = Patient::factory()->for($this->hospital)->create(['national_id' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei']);
    $this->drug = Drug::withoutGlobalScopes()->whereNull('hospital_id')->orderBy('id')->firstOrFail();
});

/**
 * @return array<string, mixed>
 */
function prescriptionPayload(array $overrides = []): array
{
    return [
        'patient_id' => test()->patient->id,
        'prescribed_on' => '1405/07/09',
        'items' => [
            ['drug_id' => test()->drug->id, 'dose' => '1', 'unit' => 'tablet', 'route' => 'PO', 'frequency' => 'QD', 'duration' => '30 days', 'quantity' => 30],
            ['drug_name' => 'Compounded cream', 'instructions' => 'Apply thinly'],
        ],
        ...$overrides,
    ];
}

function storePrescription(User $user, array $overrides = []): Prescription
{
    test()->actingAs($user)->post('/dashboard/prescriptions', prescriptionPayload($overrides))->assertRedirect();

    return Prescription::withoutGlobalScope(HospitalScope::class)->latest('id')->firstOrFail();
}

test('staff draft a prescription and only a doctor signs it', function () {
    $this->actingAs($this->nurse)->get("/dashboard/prescriptions/create?patient={$this->patient->id}")->assertOk();
    $prescription = storePrescription($this->nurse);

    $items = $prescription->items()->get();
    expect($prescription->status)->toBe(PrescriptionStatus::Draft)
        ->and($prescription->hospital_id)->toBe($this->hospital->id)
        ->and($items)->toHaveCount(2)
        // Catalog drugs are copied onto the item; other drugs are kept as typed.
        ->and($items[0]->drug_name)->toBe($this->drug->generic_name)
        ->and($items[0]->hospital_id)->toBe($this->hospital->id)
        ->and($items[1]->drug_id)->toBeNull()
        ->and($items[1]->drug_name)->toBe('Compounded cream');

    $this->actingAs($this->nurse)->get("/dashboard/prescriptions/{$prescription->id}")
        ->assertOk()
        ->assertSee($this->drug->generic_name)
        ->assertDontSee('Verification QR code');

    $this->actingAs($this->nurse)->post("/dashboard/prescriptions/{$prescription->id}/sign")->assertForbidden();
    $this->actingAs($this->doctor)->post("/dashboard/prescriptions/{$prescription->id}/sign")->assertRedirect();

    $prescription->refresh();
    expect($prescription->status)->toBe(PrescriptionStatus::Signed)
        ->and($prescription->verification_token)->toHaveLength(32)
        ->and(AuditLog::where('action', 'prescription.signed')->count())->toBe(1);

    // The printed prescription carries the QR code of its verification link.
    $this->actingAs($this->nurse)->get("/dashboard/prescriptions/{$prescription->id}")
        ->assertOk()
        ->assertSee('Verification QR code')
        ->assertSee('Dr. Prescriber');
});

test('a signed prescription is locked and can only be cancelled by a doctor', function () {
    $prescription = storePrescription($this->nurse);
    $this->actingAs($this->doctor)->post("/dashboard/prescriptions/{$prescription->id}/sign");

    $this->actingAs($this->doctor)->get("/dashboard/prescriptions/{$prescription->id}/edit")->assertForbidden();
    $this->actingAs($this->doctor)->put("/dashboard/prescriptions/{$prescription->id}", prescriptionPayload(['patient_id' => null]))->assertForbidden();
    $this->actingAs($this->doctor)->delete("/dashboard/prescriptions/{$prescription->id}")->assertForbidden();

    // The model refuses changes even outside the controllers.
    app(CurrentHospital::class)->set($this->hospital->id);
    expect(fn () => $prescription->fresh()->update(['notes' => 'changed']))->toThrow(LogicException::class)
        ->and(fn () => PrescriptionItem::where('prescription_id', $prescription->id)->first()->update(['dose' => '2']))->toThrow(LogicException::class);

    $this->actingAs($this->nurse)->post("/dashboard/prescriptions/{$prescription->id}/cancel", ['cancellation_reason' => 'Wrong dose'])->assertForbidden();
    $this->actingAs($this->doctor)->post("/dashboard/prescriptions/{$prescription->id}/cancel", ['cancellation_reason' => 'x'])->assertSessionHasErrors('cancellation_reason');
    $this->actingAs($this->doctor)->post("/dashboard/prescriptions/{$prescription->id}/cancel", ['cancellation_reason' => 'Wrong dose'])->assertRedirect();

    expect($prescription->fresh()->status)->toBe(PrescriptionStatus::Cancelled);
});

test('drafts are edited by replacing their drugs, and deleted by their author', function () {
    $prescription = storePrescription($this->nurse);

    $this->actingAs($this->nurse)->put("/dashboard/prescriptions/{$prescription->id}", [
        'prescribed_on' => '1405/07/10',
        'items' => [['drug_name' => 'Aspirin 80', 'dose' => '1', 'unit' => 'tablet']],
    ])->assertRedirect();

    expect($prescription->fresh()->prescribed_on)->toBe('1405/07/10')
        ->and($prescription->items()->pluck('drug_name')->all())->toBe(['Aspirin 80']);

    $this->actingAs($this->doctor)->delete("/dashboard/prescriptions/{$prescription->id}")->assertForbidden();
    $this->actingAs($this->nurse)->delete("/dashboard/prescriptions/{$prescription->id}")->assertRedirect();
    expect(Prescription::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('signing needs at least one drug, and invalid items are rejected', function () {
    $this->actingAs($this->nurse)->post('/dashboard/prescriptions', prescriptionPayload(['items' => []]))->assertSessionHasErrors('items');
    $this->actingAs($this->nurse)->post('/dashboard/prescriptions', prescriptionPayload(['items' => [['drug_name' => 'X', 'route' => 'nasal']]]))->assertSessionHasErrors('items.0.route');
    // A row without a drug counts as an empty row.
    $this->actingAs($this->nurse)->post('/dashboard/prescriptions', prescriptionPayload(['items' => [['dose' => '1']]]))->assertSessionHasErrors('items');
});

test('another hospital cannot read, change or sign the prescription', function () {
    $prescription = storePrescription($this->nurse);
    $other = Hospital::factory()->create();
    $strangerDoctor = User::factory()->for($other)->role(Role::Doctor)->create();

    $this->actingAs($strangerDoctor)->get("/dashboard/prescriptions/{$prescription->id}")->assertNotFound();
    $this->actingAs($strangerDoctor)->get("/dashboard/prescriptions/{$prescription->id}/edit")->assertNotFound();
    $this->actingAs($strangerDoctor)->put("/dashboard/prescriptions/{$prescription->id}", prescriptionPayload(['patient_id' => null]))->assertNotFound();
    $this->actingAs($strangerDoctor)->post("/dashboard/prescriptions/{$prescription->id}/sign")->assertNotFound();
    $this->actingAs($strangerDoctor)->delete("/dashboard/prescriptions/{$prescription->id}")->assertNotFound();

    // Nor prescribe for this hospital's patient or admission.
    $this->actingAs($strangerDoctor)->post('/dashboard/prescriptions', prescriptionPayload())->assertSessionHasErrors('patient_id');

    $admission = Admission::factory()->for($this->hospital)->for($this->patient)->create();
    $theirPatient = Patient::factory()->for($other)->create();
    $this->actingAs($strangerDoctor)->post('/dashboard/prescriptions', prescriptionPayload(['patient_id' => $theirPatient->id, 'admission_id' => $admission->id]))
        ->assertSessionHasErrors('admission_id');

    expect($prescription->fresh()->status)->toBe(PrescriptionStatus::Draft)
        ->and(Prescription::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $other->id)->count())->toBe(0);
});

test('a hospital\'s own drugs are visible only to it', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->actingAs($admin)->post('/dashboard/drugs', ['generic_name' => 'Zebracillin', 'form' => 'tablet', 'strength' => '5 mg'])->assertRedirect();
    $drug = Drug::withoutGlobalScopes()->where('generic_name', 'Zebracillin')->sole();
    expect($drug->hospital_id)->toBe($this->hospital->id);

    $this->actingAs($this->doctor)->getJson('/dashboard/drugs/search?q=zebra')->assertOk()->assertJsonPath('data.0.id', $drug->id);
    $this->actingAs($this->doctor)->get('/dashboard/drugs?q=zebra')->assertOk()->assertSee('Zebracillin');

    $other = Hospital::factory()->create();
    $stranger = User::factory()->for($other)->role(Role::Doctor)->create();
    $strangerAdmin = User::factory()->for($other)->role(Role::HospitalAdmin)->create();
    $this->actingAs($stranger)->getJson('/dashboard/drugs/search?q=zebra')->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($stranger)->get('/dashboard/drugs?q=zebra')->assertOk()->assertDontSee('Zebracillin');
    $this->actingAs($stranger)->post('/dashboard/prescriptions', prescriptionPayload([
        'patient_id' => Patient::factory()->for($other)->create()->id,
        'items' => [['drug_id' => $drug->id]],
    ]))->assertSessionHasErrors('items.0.drug_id');

    // Only the owning hospital's admin withdraws it; the shared catalog is not editable.
    $this->actingAs($strangerAdmin)->patch("/dashboard/drugs/{$drug->id}", ['is_active' => false])->assertNotFound();
    $this->actingAs($admin)->patch("/dashboard/drugs/{$this->drug->id}", ['is_active' => false])->assertForbidden();
    $this->actingAs($this->doctor)->patch("/dashboard/drugs/{$drug->id}", ['is_active' => false])->assertForbidden();
    $this->actingAs($admin)->patch("/dashboard/drugs/{$drug->id}", ['is_active' => false])->assertRedirect();

    expect($drug->fresh()->is_active)->toBeFalse();
    $this->actingAs($this->doctor)->getJson('/dashboard/drugs/search?q=zebra')->assertJsonCount(0, 'data');
});

test('drug search folds Persian letters and suggests the last order', function () {
    Drug::withoutGlobalScopes()->create(['generic_name' => 'Metoprolol', 'local_name' => 'متوپرولول', 'form' => 'tablet', 'strength' => '50 mg', 'default_dose' => '1', 'default_unit' => 'tablet']);

    // Arabic yeh and Persian digits find the Persian name.
    $this->actingAs($this->doctor)->getJson('/dashboard/drugs/search?q='.urlencode('متوپرولول ۵۰'))
        ->assertOk()
        ->assertJsonPath('data.0.label', 'Metoprolol 50 mg tablet');
    $this->actingAs($this->doctor)->getJson('/dashboard/drugs/search?q=m')->assertJsonCount(0, 'data');

    $metoprolol = Drug::withoutGlobalScopes()->where('generic_name', 'Metoprolol')->sole();
    storePrescription($this->doctor, ['items' => [['drug_id' => $metoprolol->id, 'dose' => '2', 'unit' => 'tablet', 'frequency' => 'BID']]]);

    $this->actingAs($this->doctor)->getJson('/dashboard/drugs/search?q=metoprolol')
        ->assertJsonPath('data.0.dose', '2')
        ->assertJsonPath('data.0.frequency', 'BID');
    // Another doctor gets the catalog defaults, not a colleague's order.
    $colleague = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
    $this->actingAs($colleague)->getJson('/dashboard/drugs/search?q=metoprolol')->assertJsonPath('data.0.dose', '1');
});

test('the QR verification page confirms a signed prescription without patient details', function () {
    $prescription = storePrescription($this->nurse);
    $this->actingAs($this->doctor)->post("/dashboard/prescriptions/{$prescription->id}/sign");
    $token = $prescription->fresh()->verification_token;
    auth()->logout();

    $this->get("/prescriptions/verify/{$token}")
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee($this->drug->generic_name)
        ->assertSee('Dr. Prescriber')
        ->assertDontSee('Rezaei')
        ->assertDontSee('0012345678');

    expect(AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'prescription.verified')->count())->toBe(1);

    $this->get('/prescriptions/verify/'.str_repeat('a', 32))->assertNotFound();

    // Drafts have no verification page.
    $draft = storePrescription($this->nurse);
    expect($draft->verification_token)->toBeNull();
});

test('a discharge summary has one current discharge prescription, shown on its report', function () {
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [
        'nationalID' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei',
        config('procedures.discharge.date_field') => '1405/07/09',
    ])->assertRedirect();
    $discharge = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();

    $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$discharge->id}")
        ->assertOk()
        ->assertSee('Write discharge prescription');

    $this->actingAs($this->nurse)->get("/dashboard/prescriptions/create?discharge={$discharge->id}")->assertOk();
    $prescription = storePrescription($this->nurse, ['patient_id' => null, 'discharge_study_id' => $discharge->id]);
    expect($prescription->patient_id)->toBe($discharge->patient_id)
        ->and($prescription->discharge_study_id)->toBe($discharge->id);

    $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$discharge->id}")
        ->assertOk()
        ->assertSee('Prescription not signed yet.')
        ->assertSee($this->drug->generic_name);

    // A second one is refused; the create link opens the existing one instead.
    $this->actingAs($this->nurse)->post('/dashboard/prescriptions', prescriptionPayload(['patient_id' => null, 'discharge_study_id' => $discharge->id]))
        ->assertSessionHasErrors('discharge_study_id');
    $this->actingAs($this->nurse)->get("/dashboard/prescriptions/create?discharge={$discharge->id}")
        ->assertRedirect(route('prescriptions.show', $prescription));
});

test('the patient page lists their prescriptions', function () {
    storePrescription($this->nurse);

    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Write a prescription')
        ->assertSee('View prescription');
});
