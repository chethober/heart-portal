<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Reports\ProcedureReport;
use App\Support\Diagnoses;
use App\Support\Procedures;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->payload = ['nationalID' => '0012345679', 'first_name' => 'Ali', 'last_name' => 'Rezaei', config('procedures.discharge.date_field') => '1405/07/09'];
});

test('every diagnosis has a label and an ICD-10 code', function () {
    foreach (Diagnoses::all() as $key => $entry) {
        expect($entry['label'])->not->toBe('', $key)
            ->and($entry['icd10'])->toMatch('/^[A-Z]\d{2}\.\d$/', $key);
    }
    expect(Diagnoses::label('nstemi'))->toBe('Acute NSTEMI (I21.4)');
});

test('heart\'s ICD codes map to the diagnosis list', function () {
    expect(Diagnoses::fromIcdCodes('I21.4, i48.0;X99.9'))->toBe(['nstemi', 'af_paroxysmal'])
        ->and(Diagnoses::fromIcdCodes(null))->toBe([]);
});

test('the discharge summary records final diagnoses from the list and prints their codes', function () {
    $this->actingAs($this->nurse)->get('/dashboard/procedures/discharge/create')->assertOk()
        ->assertSee('name="final_diagnosis_codes[]"', false)
        ->assertSee('Acute NSTEMI');

    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$this->payload, 'final_diagnosis_codes' => ['made_up']])
        ->assertSessionHasErrors('final_diagnosis_codes.0');

    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$this->payload, 'final_diagnosis_codes' => ['nstemi', 'af_paroxysmal'], 'finalDiagnosis' => 'LCX culprit'])
        ->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();

    expect($study->final_diagnosis_codes)->toBe(['nstemi', 'af_paroxysmal']);
    $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$study->id}")->assertOk()
        ->assertSee('Acute NSTEMI (I21.4); Paroxysmal atrial fibrillation (I48.0)')
        ->assertSee('LCX culprit');

    // A signed snapshot (JSON) reads the same.
    $snapshot = json_decode(json_encode($study->toReportArray()), true);
    expect(ProcedureReport::for('discharge', $snapshot)->sections())->toEqual(ProcedureReport::for('discharge', $study->toReportArray())->sections());
});

test('admissions keep their final diagnoses, and clearing them empties the list', function () {
    $patient = Patient::factory()->for($this->hospital)->create();
    $admission = Admission::factory()->for($patient)->create(['admitted_on' => '1405/07/01', 'icd_codes' => ['nstemi']]);
    $base = ['admitted_on' => '1405/07/01'];

    $this->actingAs($this->nurse)->get("/dashboard/patients/{$patient->id}/admissions/{$admission->id}")->assertOk()->assertSee('Acute NSTEMI (I21.4)');
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$patient->id}/admissions/{$admission->id}/edit")->assertOk()->assertSee('name="icd_codes[]"', false);

    $this->actingAs($this->nurse)->put("/dashboard/patients/{$patient->id}/admissions/{$admission->id}", [...$base, 'icd_codes' => ['vt', 'nope']])
        ->assertSessionHasErrors('icd_codes.1');
    $this->actingAs($this->nurse)->put("/dashboard/patients/{$patient->id}/admissions/{$admission->id}", [...$base, 'icd_codes' => ['vt']])->assertRedirect();
    expect($admission->fresh()->icd_codes)->toBe(['vt']);

    $this->actingAs($this->nurse)->put("/dashboard/patients/{$patient->id}/admissions/{$admission->id}", $base)->assertRedirect();
    expect($admission->fresh()->icd_codes)->toBeNull();
});

test('the help center lists the final diagnosis guide', function () {
    $this->get('/help')->assertOk()->assertSee('id="diagnoses"', false)->assertSee('I21.0');
});
