<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\AuditLog;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\RhcStudy;
use App\Models\User;
use App\Models\Ward;
use App\Support\JalaliCalendar;
use App\Support\NavigationLinks;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->ward = Ward::factory()->for($this->hospital)->create(['name' => 'CCU']);
    $this->patient = Patient::factory()->for($this->hospital)->create(['first_name' => 'Ali', 'last_name' => 'Rezaei', 'national_id' => '0012345678']);
});

/**
 * @return array<string, mixed>
 */
function admissionPayload(array $overrides = []): array
{
    return [
        'ward_id' => test()->ward->id,
        'admitted_on' => '۱۴۰۵/۷/۱',
        'admitted_at' => '08:30',
        'admission_type' => '2',
        'arrival_mode' => '3',
        'referred_from' => 'Afshar Hospital',
        'physician' => 'Dr. Karimi',
        'his_id' => 'ADM-77',
        'his_codes' => ['AdmCode' => '۱۲۳', 'SepasHospitalID' => '', 'PCode' => '55'],
        ...$overrides,
    ];
}

test('clinical staff admit a patient', function () {
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/admissions/create")
        ->assertOk()
        ->assertSee('CCU')
        ->assertSee(JalaliCalendar::today());

    $response = $this->actingAs($this->nurse)->post("/dashboard/patients/{$this->patient->id}/admissions", admissionPayload());
    $admission = Admission::sole();
    $response->assertRedirect(route('admissions.show', [$this->patient, $admission]));

    expect($admission)
        ->hospital_id->toBe($this->hospital->id)
        ->patient_id->toBe($this->patient->id)
        ->ward_id->toBe($this->ward->id)
        ->ward->toBe('CCU')
        ->admitted_on->toBe('1405/07/01')
        ->admission_type->toBe(2)
        ->arrival_mode->toBe(3)
        ->referred_from->toBe('Afshar Hospital')
        ->his_id->toBe('ADM-77')
        ->his_codes->toBe(['PCode' => '55', 'AdmCode' => '123'])
        ->isDischarged()->toBeFalse();
    expect(AuditLog::where('action', 'admission.created')->where('subject_id', $admission->id)->exists())->toBeTrue();

    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}")->assertSee('CCU')->assertSee('1405/07/01');
    $this->actingAs($this->nurse)->get(route('admissions.show', [$this->patient, $admission]))
        ->assertOk()
        ->assertSee('Afshar Hospital')
        ->assertSee('Dr. Karimi')
        ->assertSee('ADM-77')
        ->assertSee('AdmCode')
        ->assertSee(route('admissions.discharge', [$this->patient, $admission]), false);
});

test('the referring hospital is kept only for transfers in', function () {
    $this->actingAs($this->nurse)->post("/dashboard/patients/{$this->patient->id}/admissions", admissionPayload(['arrival_mode' => '4']));

    expect(Admission::sole()->referred_from)->toBeNull();
});

test('admissions are validated', function (array $fields, string $error) {
    $this->actingAs($this->nurse)->post("/dashboard/patients/{$this->patient->id}/admissions", admissionPayload($fields))
        ->assertSessionHasErrors($error);
    expect(Admission::count())->toBe(0);
})->with([
    'missing date' => [['admitted_on' => ''], 'admitted_on'],
    'not a date' => [['admitted_on' => '1405/13/40'], 'admitted_on'],
    'bad time' => [['admitted_at' => '25:00'], 'admitted_at'],
    'unknown type' => [['admission_type' => '7'], 'admission_type'],
    'unknown arrival' => [['arrival_mode' => '6'], 'arrival_mode'],
    'discharge before admission' => [['discharged_on' => '1405/06/30'], 'discharged_on'],
    'unknown HIS code' => [['his_codes' => ['Other' => '1']], 'his_codes'],
]);

test('an HIS admission number is unique within the hospital', function () {
    Admission::factory()->for($this->patient)->create(['his_id' => 'ADM-77']);
    $this->actingAs($this->nurse)->post("/dashboard/patients/{$this->patient->id}/admissions", admissionPayload())->assertSessionHasErrors('his_id');

    // Another hospital may reuse it.
    $other = User::factory()->for(Hospital::factory())->role(Role::Nurse)->create();
    $otherPatient = Patient::factory()->for($other->hospital)->create();
    $this->actingAs($other)->post("/dashboard/patients/{$otherPatient->id}/admissions", admissionPayload(['ward_id' => null]))->assertSessionHasNoErrors();
});

test('admissions are edited and discharged with the length of stay', function () {
    $admission = Admission::factory()->for($this->patient)->create(['ward_id' => $this->ward->id, 'admitted_on' => '1405/06/31']);

    $this->actingAs($this->nurse)->get(route('admissions.edit', [$this->patient, $admission]))->assertOk()->assertSee('1405/06/31');
    $this->actingAs($this->nurse)->put(route('admissions.update', [$this->patient, $admission]), admissionPayload(['admitted_on' => '1405/06/31', 'physician' => 'Dr. Amini', 'his_id' => '']))
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($admission->fresh()->physician)->toBe('Dr. Amini')
        ->and(AuditLog::where('action', 'admission.updated')->sole()->changes['physician'])->toBe([null, 'Dr. Amini']);

    $this->actingAs($this->nurse)->post(route('admissions.discharge', [$this->patient, $admission]), ['discharged_on' => '1405/06/30'])->assertSessionHasErrors('discharged_on');
    $this->actingAs($this->nurse)->post(route('admissions.discharge', [$this->patient, $admission]), ['discharged_on' => '۱۴۰۵/۷/۱', 'discharged_at' => '14:00'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $admission->refresh();
    expect($admission->discharged_on)->toBe('1405/07/01')
        ->and($admission->discharged_at)->toBe('14:00')
        ->and($admission->lengthOfStay())->toBe(1)
        ->and(AuditLog::where('action', 'admission.discharged')->exists())->toBeTrue();

    // Already discharged.
    $this->actingAs($this->nurse)->post(route('admissions.discharge', [$this->patient, $admission]), ['discharged_on' => '1405/07/02'])->assertStatus(409);
});

test('a current stay is counted to today in the hospital timezone', function () {
    // 1405/07/21 starts at 20:30 UTC on 2026-10-12 in Tehran.
    Carbon::setTestNow('2026-10-12 21:00:00');
    $admission = Admission::factory()->for($this->patient)->make(['admitted_on' => '1405/07/20']);

    expect(JalaliCalendar::today())->toBe('1405/07/21')
        ->and($admission->lengthOfStay())->toBe(1)
        ->and(JalaliCalendar::daysBetween('1405/12/29', '1406/01/01'))->toBe(1)
        ->and(JalaliCalendar::daysBetween('1405/13/01', '1406/01/01'))->toBeNull();

    Carbon::setTestNow();
});

test('an admission cannot be opened through another patient', function () {
    $admission = Admission::factory()->for($this->patient)->create();
    $otherPatient = Patient::factory()->for($this->hospital)->create();

    $this->actingAs($this->nurse)->get("/dashboard/patients/{$otherPatient->id}/admissions/{$admission->id}")->assertNotFound();
    $this->actingAs($this->nurse)->put("/dashboard/patients/{$otherPatient->id}/admissions/{$admission->id}", admissionPayload())->assertNotFound();
});

test('another hospital cannot read, change or discharge admissions, nor use its wards', function () {
    $admission = Admission::factory()->for($this->patient)->create(['physician' => 'Dr. Demo']);
    $outsider = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();
    $outsidersPatient = Patient::factory()->for($outsider->hospital)->create();
    $base = "/dashboard/patients/{$this->patient->id}/admissions";

    $this->actingAs($outsider)->get("{$base}/{$admission->id}")->assertNotFound();
    $this->actingAs($outsider)->get("{$base}/{$admission->id}/edit")->assertNotFound();
    $this->actingAs($outsider)->put("{$base}/{$admission->id}", admissionPayload(['physician' => 'Intruder']))->assertNotFound();
    $this->actingAs($outsider)->post("{$base}/{$admission->id}/discharge", ['discharged_on' => '1405/08/01'])->assertNotFound();
    $this->actingAs($outsider)->post($base, admissionPayload())->assertNotFound();

    // Our ward is not a ward of theirs.
    $this->actingAs($outsider)->post("/dashboard/patients/{$outsidersPatient->id}/admissions", admissionPayload())->assertSessionHasErrors('ward_id');
    $this->actingAs($outsider)->get('/dashboard/patients')->assertOk()->assertDontSee('Rezaei')->assertDontSee('CCU');

    $this->actingAs($this->nurse);
    expect($admission->fresh())->physician->toBe('Dr. Demo')->discharged_on->toBeNull();
});

test('the patients page lists current admissions by ward until something is searched', function () {
    $icu = Ward::factory()->for($this->hospital)->create(['name' => 'ICU']);
    Admission::factory()->for($this->patient)->create(['ward_id' => $this->ward->id, 'ward' => 'CCU', 'admitted_on' => '1405/07/01']);
    Admission::factory()->for(Patient::factory()->for($this->hospital)->state(['last_name' => 'Karimi']))->create(['ward_id' => $icu->id, 'ward' => 'ICU']);
    Admission::factory()->for(Patient::factory()->for($this->hospital)->state(['last_name' => 'Gone']))->create(['ward' => 'CCU', 'discharged_on' => '1405/07/02']);
    Admission::factory()->for(Patient::factory()->state(['last_name' => 'Elsewhere']))->create(['ward' => 'CCU']);

    $this->actingAs($this->nurse)->get('/dashboard/patients')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee('Karimi')
        ->assertDontSee('Gone')
        ->assertDontSee('Elsewhere')
        ->assertSee(route('patients.show', $this->patient), false)
        ->assertSee(e(route('procedures.create', ['angiography', 'nationalID' => '0012345678', 'admission' => Admission::where('patient_id', $this->patient->id)->value('id')])), false);

    $this->actingAs($this->nurse)->get("/dashboard/patients?ward={$icu->id}")
        ->assertOk()
        ->assertSee('Karimi')
        ->assertDontSee('Rezaei');

    // A search shows the matches instead of the ward list.
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=Karimi')->assertOk()->assertDontSee('Patients in hospital');

    // The old ward patients page and its links lead to the patients page.
    $this->actingAs($this->nurse)->get("/dashboard/ward-patients?ward={$icu->id}")
        ->assertRedirect(route('patients.index', ['ward' => $icu->id]))->assertStatus(301);
    $this->actingAs($this->nurse)->get('/dashboard/patients')->assertDontSee('/dashboard/ward-patients');
});

test('the admission page lists the studies recorded during it', function () {
    $admission = Admission::factory()->for($this->patient)->create();
    $angio = AngiographyStudy::create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id, 'admission_id' => $admission->id]);
    $unlinked = RhcStudy::create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id]);
    $echo = EchoStudy::factory()->create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id, 'admission_id' => $admission->id]);

    $this->actingAs($this->nurse)->get(route('admissions.show', [$this->patient, $admission]))
        ->assertOk()
        ->assertSee(route('procedures.show', ['angiography', $angio]), false)
        ->assertDontSee(route('procedures.show', ['rhc', $unlinked]), false)
        ->assertSee(route('studies.show', $echo), false)
        ->assertSee(e(route('procedures.create', ['rhc', 'nationalID' => '0012345678', 'admission' => $admission->id])), false);
});

test('procedure forms offer the patient\'s admissions', function () {
    $admission = Admission::factory()->for($this->patient)->create(['ward' => 'CCU', 'admitted_on' => '1405/07/01']);
    Admission::factory()->for(Patient::factory()->for($this->hospital))->create(['admitted_on' => '1399/01/01']);

    $this->actingAs($this->nurse)->get("/dashboard/procedures/angiography/create?nationalID=0012345678&admission={$admission->id}")
        ->assertOk()
        ->assertSee('<select id="admission_id" name="admission_id"', false)
        ->assertSee('<option value="'.$admission->id.'" selected>1405/07/01 · CCU', false)
        ->assertDontSee('1399/01/01');

    // Unknown patient: the ?admission= link is still carried.
    $this->actingAs($this->nurse)->get("/dashboard/procedures/angiography/create?admission={$admission->id}")
        ->assertSee('<option value="'.$admission->id.'" selected>Admission #'.$admission->id.'</option>', false);

    $study = AngiographyStudy::create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id, 'admission_id' => $admission->id]);
    $this->actingAs($this->nurse)->get("/dashboard/procedures/angiography/{$study->id}/edit")
        ->assertOk()
        ->assertSee('<option value="'.$admission->id.'" selected>', false);
});

test('the worklist lists procedure drafts with echo studies', function () {
    $angio = AngiographyStudy::create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id, 'created_by' => $this->nurse->id]);
    $signed = RhcStudy::create(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id]);
    $signed->forceFill(['status' => StudyStatus::Final])->save();
    $other = AngiographyStudy::create(['hospital_id' => Hospital::factory()->create()->id, 'patient_id' => Patient::factory()->create()->id]);
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    $this->actingAs($this->nurse)->get('/dashboard/worklist')
        ->assertOk()
        ->assertSee('Procedure drafts')
        ->assertSee(route('procedures.show', ['angiography', $angio]), false)
        ->assertSee(route('procedures.edit', ['angiography', $angio]), false)
        ->assertDontSee(route('procedures.finalize', ['angiography', $angio]), false)
        ->assertDontSee(route('procedures.show', ['rhc', $signed]), false)
        ->assertDontSee(route('procedures.show', ['angiography', $other]), false)
        ->assertDontSee('No studies found')
        ->assertViewHas('counts', fn ($counts) => $counts->isEmpty());

    $this->actingAs($doctor)->get('/dashboard/worklist')->assertSee(route('procedures.finalize', ['angiography', $angio]), false);
    $this->actingAs($this->nurse)->get('/dashboard/worklist?search=rezaei')->assertSee(route('procedures.show', ['angiography', $angio]), false);
    $this->actingAs($this->nurse)->get('/dashboard/worklist?search=nobody')->assertDontSee(route('procedures.show', ['angiography', $angio]), false);
    $this->actingAs($this->nurse)->get('/dashboard/worklist?status=final')->assertDontSee('Procedure drafts');

    // The navigation counts procedure drafts too.
    expect(collect(NavigationLinks::for($this->nurse))->firstWhere('label', 'Worklist')['count'])->toBe(1);
});
