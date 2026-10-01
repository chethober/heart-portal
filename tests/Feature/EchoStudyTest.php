<?php

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
});

function echoPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'nationalID' => '۰۰۱۲۳۴۵۶۷۸',
        'dob' => '1360/02/15',
        'gender' => 'Male',
        'echo_date' => '1405/07/09',
        'Height' => 180,
        'Weight' => 80,
        'lv_EF' => 35,
        'lv_LVEDV' => 150,
        'lv_size' => 'Auto',
        'lv_rwma_checked' => '1',
        'other_des_lv' => 'apical thinning <script>alert(1)</script>',
        'doctor' => 'Dr. Karimi',
        ...$overrides,
    ];
}

test('technician saves a study and the patient is created with encrypted identifiers', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())
        ->assertCreated()
        ->assertJson(['success' => true]);

    $patient = Patient::withoutGlobalScope(HospitalScope::class)->sole();
    expect($patient->hospital_id)->toBe($this->hospital->id)
        ->and($patient->national_id)->toBe('0012345678');

    $raw = DB::table('patients')->first();
    expect($raw->national_id)->not->toContain('0012345678');

    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->created_by)->toBe($this->tech->id)
        ->and($study->lv_EF)->toBe(35.0)
        ->and($study->lv_rwma_checked)->toBeTrue();
});

test('a second study reuses the patient and keeps demographics current', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['nationalID' => '0012345678', 'phone_number' => '09120000000', 'dob' => null]))->assertCreated();

    $patient = Patient::withoutGlobalScope(HospitalScope::class)->sole();
    expect($patient->studies()->count())->toBe(2)
        ->and($patient->phone_number)->toBe('09120000000')
        ->and($patient->dob)->toBe('1360/02/15');
});

test('unknown fields and malformed values are rejected', function () {
    $this->actingAs($this->tech)
        ->postJson('/dashboard/studies', echoPayload(['id) VALUES (1); DROP TABLE patients; --' => 'x', 'lv_EF' => 'lots']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lv_EF']);

    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['hospital_id' => 999]))
        ->assertJsonValidationErrors(['hospital_id']);

    expect(EchoStudy::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('required patient fields are enforced', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['first_name' => '', 'nationalID' => '']))
        ->assertJsonValidationErrors(['first_name', 'nationalID']);
});

test('history and report render with derived values and sanitized free text', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee(route('studies.show', $study), false);

    $this->actingAs($this->tech)->get("/dashboard/studies/{$study->id}")
        ->assertOk()
        ->assertSee($this->hospital->name)
        ->assertSeeText('There is mild LV enlargement')
        ->assertSee('apical thinning', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('live preview renders unsaved form data', function () {
    $this->actingAs($this->tech)
        ->post('/dashboard/studies/preview', [...echoPayload(), 'lv_size' => 'Manual', 'lv_size_manual' => 'Borderline LV size'])
        ->assertOk()
        ->assertSee('Borderline LV size');
});

test('hospitals cannot see each others patients or studies', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();

    $intruder = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($intruder)->get("/dashboard/studies/{$study->id}")->assertNotFound();
    $this->actingAs($intruder)->delete("/dashboard/studies/{$study->id}")->assertNotFound();
    $this->actingAs($intruder)->get('/dashboard/patients?nationalID=0012345678')->assertOk()->assertDontSee('Rezaei');

    // Same national ID in another hospital creates a separate patient record.
    $this->actingAs($intruder)->postJson('/dashboard/studies', echoPayload(['first_name' => 'Other']))->assertCreated();
    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(2);
    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')->assertDontSee('Other');
});

test('hospital admins can view but not create studies', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get('/dashboard/studies/create')->assertForbidden();
    $this->actingAs($admin)->postJson('/dashboard/studies', echoPayload())->assertForbidden();
    $this->actingAs($admin)->get('/dashboard/patients')->assertOk()->assertDontSee(route('studies.create'), false);
});

test('form page and doctor suggestions load', function () {
    User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr. Ahmadi']);
    User::factory()->role(Role::Doctor)->create(['name' => 'Dr. Elsewhere']);

    $this->actingAs($this->tech)->get('/dashboard/studies/create')->assertOk()->assertSee('echoForm', false);
    $this->actingAs($this->tech)->getJson('/dashboard/doctors')->assertOk()->assertJson(['Dr. Ahmadi'])->assertJsonMissing(['Dr. Elsewhere']);
});
