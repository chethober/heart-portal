<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Support\NationalId;
use App\Support\Procedures;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->patient = Patient::factory()->for($this->hospital)->create([
        'national_id' => '0012345679', 'first_name' => 'Sara', 'last_name' => 'Ahmadi', 'dob' => '1365/03/02', 'gender' => 'Female',
    ]);
});

test('the national ID check digit is verified like heart did', function () {
    expect(NationalId::isValid('0012345679'))->toBeTrue()
        ->and(NationalId::isValid('۰۰۱۲۳۴۵۶۷۹'))->toBeTrue()
        ->and(NationalId::isValid('0012345678'))->toBeFalse()
        ->and(NationalId::isValid('1111111111'))->toBeFalse()
        ->and(NationalId::isValid('12345'))->toBeFalse();
});

test('a registered national ID returns the patient\'s details and admissions', function () {
    $admission = Admission::factory()->for($this->patient)->create(['ward' => 'CCU', 'admitted_on' => '1405/07/01']);

    $this->actingAs($this->nurse)->getJson('/dashboard/patients/lookup?nationalID=۰۰۱۲۳۴۵۶۷۹')
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('patient.first_name', 'Sara')
        ->assertJsonPath('patient.last_name', 'Ahmadi')
        ->assertJsonPath('patient.dob', '1365/03/02')
        ->assertJsonPath('patient.gender', 'Female')
        ->assertJsonPath('patient.admissions.0.id', $admission->id);
});

test('an unknown national ID says whether it is a valid one', function () {
    $this->actingAs($this->nurse)->getJson('/dashboard/patients/lookup?nationalID=0012345678')
        ->assertOk()->assertJson(['valid' => false, 'patient' => null]);
});

test('another hospital\'s patients are never looked up', function () {
    $outsider = User::factory()->for(Hospital::factory())->role(Role::Nurse)->create();

    $this->actingAs($outsider)->getJson('/dashboard/patients/lookup?nationalID=0012345679')
        ->assertOk()->assertJson(['patient' => null]);
});

test('the lookup needs patient access', function () {
    $platformAdmin = User::factory()->role(Role::PlatformAdmin)->create();

    $this->actingAs($platformAdmin)->getJson('/dashboard/patients/lookup?nationalID=0012345679')->assertForbidden();
});

test('the patient lookup lives on the dashboard patient step, not the study forms', function () {
    $response = $this->actingAs($this->nurse)->get('/dashboard')->assertOk();
    foreach (['first_name', 'last_name', 'nationalID', 'dob', 'phone_number', 'gender', 'study_date', 'admission_number'] as $field) {
        $response->assertSee('name="'.$field.'"', false);
    }
    $response->assertSee('window.patientLookup =', false)->assertSee('id="patient-admissions"', false);
    foreach (['echo', ...array_keys(Procedures::MODELS)] as $form) {
        $this->get(startStudyUrl($this->nurse, $form))->assertOk()
            ->assertDontSee('window.patientLookup =', false)->assertDontSee('role="tablist"', false)
            ->assertSee('Save Locally')->assertSee('Live Preview');
    }
});

test('echo studies can be linked to one of the patient\'s admissions', function () {
    $admission = Admission::factory()->for($this->patient)->create();
    $other = Admission::factory()->for(Patient::factory()->for($this->hospital))->create();
    $payload = ['first_name' => 'Sara', 'last_name' => 'Ahmadi', 'nationalID' => '0012345679', 'echo_date' => '1405/07/09'];

    $this->actingAs($this->nurse)->postJson('/dashboard/studies', [...$payload, 'admission_id' => $other->id])
        ->assertUnprocessable()->assertJsonValidationErrors('admission_id');

    $this->actingAs($this->nurse)->postJson('/dashboard/studies', [...$payload, 'admission_id' => $admission->id])->assertCreated();

    $study = EchoStudy::sole();
    expect($study->admission_id)->toBe($admission->id)
        ->and($study->toFormArray()['admission_id'])->toBe((string) $admission->id);
});
