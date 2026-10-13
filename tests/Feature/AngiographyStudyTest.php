<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\ProcedureStudyVersion;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

function angiographyPayload(array $overrides = []): array
{
    return [
        'nationalID' => '0012345678',
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'dob' => '1350/01/01',
        'gender' => 'Male',
        'DateOf_Date_of_angiography' => '1405/07/09',
        'angiography_status' => 'Elective_angiography',
        'native_coronary_angiography' => '1',
        'lad_Vessel_patency' => '3',
        'lad_proximal_part_segment' => '2',
        'lad_prox_Stenotic' => '3',
        'lad_pci_Description' => 'Tight proximal LAD lesion.',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr. Signer']);
});

function storeAngiography(User $user, array $overrides = []): AngiographyStudy
{
    test()->actingAs($user)->post('/dashboard/procedures/angiography', angiographyPayload($overrides))->assertRedirect();

    return AngiographyStudy::withoutGlobalScope(HospitalScope::class)->latest('id')->firstOrFail();
}

test('the form is built from the heart field list', function () {
    $this->actingAs($this->tech)->get('/dashboard/procedures/angiography/create')
        ->assertOk()
        ->assertSee('Coronary Angiography')
        ->assertSee('Vessl patency')
        ->assertSee('very severe stenosis(90-99%)');
});

test('clinical staff record a draft and the patient is created', function () {
    $study = storeAngiography($this->tech);

    expect($study->status)->toBe(StudyStatus::Draft)
        ->and($study->hospital_id)->toBe($this->hospital->id)
        ->and($study->created_by)->toBe($this->tech->id)
        ->and($study->lad_prox_Stenotic)->toBe('3')
        ->and($study->native_coronary_angiography)->toBeTrue()
        ->and($study->patient->national_id)->toBe('0012345678')
        ->and(AuditLog::where('action', 'angiography.created')->count())->toBe(1);

    $this->actingAs($this->tech)->get("/dashboard/procedures/angiography/{$study->id}")
        ->assertOk()
        ->assertSee('DRAFT — NOT SIGNED')
        ->assertSee('Severe stenosis(70-89%) at proximal part segment')
        ->assertSee('Tight proximal LAD lesion.');
});

test('a new study can be started from a known patient', function () {
    storeAngiography($this->tech);

    $this->actingAs($this->tech)->get('/dashboard/procedures/angiography/create?nationalID=0012345678')
        ->assertOk()
        ->assertSee('value="Rezaei"', false);

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Coronary Angiography')
        ->assertSee('1405/07/09');
});

test('only listed fields and codes are accepted', function () {
    $this->actingAs($this->tech)
        ->post('/dashboard/procedures/angiography', angiographyPayload(['lad_prox_Stenotic' => '9', 'status' => 'final', 'evil' => 'x']))
        ->assertSessionHasErrors(['lad_prox_Stenotic', 'status', 'evil']);

    expect(AngiographyStudy::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('fields hidden by their condition are cleared, and unticked boxes are saved as empty', function () {
    $study = storeAngiography($this->tech, ['lad_other_Vessel_patency' => 'should be dropped']);
    expect($study->lad_other_Vessel_patency)->toBeNull();

    $payload = angiographyPayload(['lad_Vessel_patency' => '99', 'lad_other_Vessel_patency' => 'ectatic']);
    unset($payload['native_coronary_angiography']);

    $this->actingAs($this->tech)->put("/dashboard/procedures/angiography/{$study->id}", $payload)->assertRedirect();

    $study->refresh();
    expect($study->lad_other_Vessel_patency)->toBe('ectatic')
        ->and($study->lad_prox_Stenotic)->toBeNull()
        ->and($study->native_coronary_angiography)->toBeNull();
});

test('only doctors sign, and signed studies are only amended by a doctor with a reason', function () {
    $study = storeAngiography($this->tech);

    $this->actingAs($this->tech)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertForbidden();
    $this->actingAs($this->doctor)->post("/dashboard/procedures/angiography/{$study->id}/finalize")
        ->assertRedirect(route('procedures.show', ['angiography', $study]));

    $study->refresh();
    expect($study->status)->toBe(StudyStatus::Final)
        ->and($study->reported_by)->toBe($this->doctor->id)
        ->and($study->versions()->count())->toBe(1)
        ->and(ProcedureStudyVersion::withoutGlobalScope(HospitalScope::class)->sole()->study_type)->toBe('angiography');

    $this->actingAs($this->tech)->get("/dashboard/procedures/angiography/{$study->id}/edit")->assertForbidden();
    $this->actingAs($this->doctor)->delete("/dashboard/procedures/angiography/{$study->id}")->assertForbidden();

    $this->actingAs($this->doctor)
        ->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload(['lad_prox_Stenotic' => '4']))
        ->assertSessionHasErrors('amendment_reason');

    $this->actingAs($this->doctor)
        ->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload(['lad_prox_Stenotic' => '4', 'amendment_reason' => 'Re-read the films']))
        ->assertRedirect();

    $study->refresh();
    expect($study->status)->toBe(StudyStatus::Amended)
        ->and($study->lad_prox_Stenotic)->toBe('4')
        ->and($study->versions()->count())->toBe(2)
        ->and($study->versions()->first()->data['lad_prox_Stenotic'])->toBe('3');

    $this->actingAs($this->tech)->get("/dashboard/procedures/angiography/{$study->id}?version=1")
        ->assertOk()
        ->assertSee('Showing signed version 1 of 2')
        ->assertSee('Severe stenosis(70-89%) at proximal part segment');
});

test('drafts can be deleted by their author', function () {
    $study = storeAngiography($this->tech);

    $this->actingAs($this->doctor)->delete("/dashboard/procedures/angiography/{$study->id}")->assertForbidden();
    $this->actingAs($this->tech)->delete("/dashboard/procedures/angiography/{$study->id}")->assertRedirect();

    expect(AngiographyStudy::withoutGlobalScope(HospitalScope::class)->withTrashed()->sole()->trashed())->toBeTrue();
});

test('another hospital cannot read or modify the study', function () {
    $study = storeAngiography($this->tech);
    $other = Hospital::factory()->create();
    $otherDoctor = User::factory()->for($other)->role(Role::Doctor)->create();
    $otherAdmin = User::factory()->for($other)->role(Role::HospitalAdmin)->create();

    $this->actingAs($otherDoctor);
    expect(AngiographyStudy::count())->toBe(0);

    $this->get("/dashboard/procedures/angiography/{$study->id}")->assertNotFound();
    $this->get("/dashboard/procedures/angiography/{$study->id}/edit")->assertNotFound();
    $this->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload())->assertNotFound();
    $this->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertNotFound();
    $this->actingAs($otherAdmin)->delete("/dashboard/procedures/angiography/{$study->id}")->assertNotFound();

    // The same national ID in the other hospital is a separate patient with no studies.
    $this->actingAs($otherDoctor)->post('/dashboard/procedures/angiography', angiographyPayload())->assertRedirect();
    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(2)
        ->and($study->fresh()->status)->toBe(StudyStatus::Draft)
        ->and(AngiographyStudy::count())->toBe(1);
});

test('a study links only to an admission of the same patient and hospital', function () {
    $study = storeAngiography($this->tech);
    $own = Admission::factory()->for($study->patient)->create();
    $otherPatients = Admission::factory()->for(Patient::factory()->for($this->hospital))->create();
    $otherHospitals = Admission::factory()->create();

    $this->actingAs($this->tech)
        ->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload(['admission_id' => $otherHospitals->id]))
        ->assertSessionHasErrors('admission_id');
    $this->actingAs($this->tech)
        ->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload(['admission_id' => $otherPatients->id]))
        ->assertSessionHasErrors('admission_id');
    $this->actingAs($this->tech)
        ->put("/dashboard/procedures/angiography/{$study->id}", angiographyPayload(['admission_id' => $own->id]))
        ->assertRedirect();

    expect($study->fresh()->admission_id)->toBe($own->id);
});

test('admissions are hidden from other hospitals', function () {
    $admission = Admission::factory()->for(Patient::factory()->for($this->hospital))->create();

    $this->actingAs($this->tech);
    expect(Admission::find($admission->id))->not->toBeNull();

    $this->actingAs(User::factory()->role(Role::Doctor)->create());
    expect(Admission::find($admission->id))->toBeNull();
});

test('unknown procedures and non-numeric ids are not found', function () {
    $this->actingAs($this->tech)->get('/dashboard/procedures/unknown/create')->assertNotFound();
    $this->actingAs($this->tech)->get('/dashboard/procedures/angiography/abc')->assertNotFound();
});

test('a patient not yet on record can be started from the patient search', function () {
    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0099999999')
        ->assertOk()
        ->assertSee(route('procedures.create', ['angiography', 'nationalID' => '0099999999']), false);

    $this->actingAs($this->tech)->get('/dashboard/procedures/angiography/create?nationalID=0099999999')
        ->assertOk()
        ->assertSee('value="0099999999"', false);
});

test('angiography actions appear in the audit log', function () {
    storeAngiography($this->tech);
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get('/dashboard/audit')
        ->assertOk()
        ->assertSee('angiography.created');
});
