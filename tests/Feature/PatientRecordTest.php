<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->patient = Patient::factory()->for($this->hospital)->create(['first_name' => 'Ali', 'last_name' => 'Rezaei', 'dob' => '1350/01/01']);
});

/**
 * @return array<string, string>
 */
function demographicsPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'father_name' => 'Hassan',
        'dob' => '۱۳۵۰/۱/۱',
        'gender' => 'Male',
        'nationality' => '99999',
        'nationality_other' => 'Pakistani',
        'marital_status' => '2',
        'occupation' => 'Teacher',
        'phone_number' => '0912 345-6789',
        'home_phone' => '02133334444',
        'emergency_phone' => '09121112222',
        'email' => 'ali@example.com',
        'address' => 'Tehran, Valiasr St.',
        ...$overrides,
    ];
}

test('the patient record shows demographics and admissions', function () {
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}")
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee('No admissions recorded')
        ->assertSee(route('admissions.create', $this->patient), false)
        ->assertSee(route('patients.edit', $this->patient), false);

    expect(AuditLog::where('action', 'patient.viewed')->where('subject_id', $this->patient->id)->exists())->toBeTrue();

    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID='.$this->patient->national_id)
        ->assertSee(route('patients.show', $this->patient), false);
});

test('clinical staff edit the demographics heart keeps, with contact details encrypted', function () {
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/edit")->assertOk()->assertSee('Father');

    $this->actingAs($this->nurse)->put("/dashboard/patients/{$this->patient->id}", demographicsPayload())
        ->assertRedirect(route('patients.show', $this->patient))
        ->assertSessionHasNoErrors();

    $patient = $this->patient->fresh();
    expect($patient)
        ->father_name->toBe('Hassan')
        ->dob->toBe('1350/01/01')
        ->nationality->toBe('99999')
        ->nationalityLabel()->toBe('Pakistani')
        ->marital_status->toBe(2)
        ->phone_number->toBe('09123456789')
        ->home_phone->toBe('02133334444')
        ->email->toBe('ali@example.com')
        ->address->toBe('Tehran, Valiasr St.');

    $raw = DB::table('patients')->where('id', $patient->id)->first();
    foreach (['phone_number', 'home_phone', 'emergency_phone', 'email', 'address'] as $column) {
        expect($raw->{$column})->not->toBeNull()->not->toContain($patient->{$column});
    }

    $changes = AuditLog::where('action', 'patient.updated')->sole()->changes;
    expect($changes['address'])->toBe('[redacted]')
        ->and($changes['father_name'])->toBe([null, 'Hassan']);

    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}")
        ->assertSee('Hassan')->assertSee('Pakistani')->assertSee('Married')->assertSee('Teacher');
});

test('the patient form validates its fields', function () {
    $this->actingAs($this->nurse)->put("/dashboard/patients/{$this->patient->id}", demographicsPayload([
        'dob' => '1350/13/40', 'nationality' => 'xx', 'marital_status' => '7', 'email' => 'nope', 'home_phone' => 'call me',
    ]))->assertSessionHasErrors(['dob', 'nationality', 'marital_status', 'email', 'home_phone']);

    // "Other" text is dropped unless the nationality is other.
    $this->actingAs($this->nurse)->put("/dashboard/patients/{$this->patient->id}", demographicsPayload(['nationality' => 'ir']))->assertSessionHasNoErrors();
    expect($this->patient->fresh())->nationality_other->toBeNull()->nationalityLabel()->toBe('Iranian');
});

test('demographics edits are limited to clinical staff', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get("/dashboard/patients/{$this->patient->id}")->assertOk()->assertDontSee(route('patients.edit', $this->patient), false);
    $this->actingAs($admin)->put("/dashboard/patients/{$this->patient->id}", demographicsPayload())->assertForbidden();
});

test('another hospital cannot read or change the patient record', function () {
    $outsider = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();

    $this->actingAs($outsider)->get("/dashboard/patients/{$this->patient->id}")->assertNotFound();
    $this->actingAs($outsider)->get("/dashboard/patients/{$this->patient->id}/edit")->assertNotFound();
    $this->actingAs($outsider)->put("/dashboard/patients/{$this->patient->id}", demographicsPayload(['father_name' => 'Intruder']))->assertNotFound();

    $this->actingAs($this->nurse);
    expect($this->patient->fresh()->father_name)->toBeNull();
});
