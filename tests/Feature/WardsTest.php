<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Models\Ward;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
});

test('hospital admins add, rename and deactivate wards', function () {
    $this->actingAs($this->admin)->get('/dashboard/wards')->assertOk()->assertSee('No wards yet');

    $this->actingAs($this->admin)->post('/dashboard/wards', ['name' => ' CCU '])->assertRedirect()->assertSessionHasNoErrors();
    $ward = Ward::sole();
    expect($ward->name)->toBe('CCU')
        ->and($ward->hospital_id)->toBe($this->hospital->id)
        ->and($ward->is_active)->toBeTrue();

    $this->actingAs($this->admin)->post('/dashboard/wards', ['name' => 'CCU'])->assertSessionHasErrors('name');

    $this->actingAs($this->admin)->patch("/dashboard/wards/{$ward->id}", ['name' => 'Coronary Care', 'is_active' => '0'])->assertRedirect();
    expect($ward->fresh())->name->toBe('Coronary Care')->is_active->toBeFalse();

    $this->actingAs($this->admin)->get('/dashboard/wards')->assertSee('Coronary Care');
    expect(AuditLog::where('action', 'ward.created')->count())->toBe(1)
        ->and(AuditLog::where('action', 'ward.updated')->sole()->changes)->toHaveKeys(['name', 'is_active']);
});

test('only hospital admins manage wards', function () {
    $this->actingAs($this->nurse)->get('/dashboard/wards')->assertForbidden();
    $this->actingAs($this->nurse)->post('/dashboard/wards', ['name' => 'ICU'])->assertForbidden();

    $this->actingAs($this->admin)->get('/dashboard/patients')->assertSee(route('wards.index'), false);
    $this->actingAs($this->nurse)->get('/dashboard/patients')->assertDontSee(route('wards.index'), false);
});

test('wards are hidden from other hospitals and names are unique per hospital', function () {
    $ward = Ward::factory()->for($this->hospital)->create(['name' => 'ICU']);
    $outsider = User::factory()->for(Hospital::factory())->role(Role::HospitalAdmin)->create();

    $this->actingAs($outsider)->get('/dashboard/wards')->assertOk()->assertDontSee('ICU');
    $this->actingAs($outsider)->patch("/dashboard/wards/{$ward->id}", ['name' => 'Hacked', 'is_active' => '0'])->assertNotFound();
    expect($ward->fresh()->name)->toBe('ICU');

    // Another hospital may use the same name.
    $this->actingAs($outsider)->post('/dashboard/wards', ['name' => 'ICU'])->assertSessionHasNoErrors();
    expect(Ward::withoutGlobalScope(HospitalScope::class)->where('name', 'ICU')->count())->toBe(2);
});

test('admissions recorded with only a ward name are linked to that ward', function () {
    $patient = Patient::factory()->for($this->hospital)->create();
    $first = Admission::factory()->for($patient)->create(['ward' => 'Post-CCU']);
    $second = Admission::factory()->for($patient)->create(['ward' => 'Post-CCU']);

    $this->actingAs($this->admin);
    $ward = Ward::sole();
    expect($ward->name)->toBe('Post-CCU')
        ->and($first->ward_id)->toBe($ward->id)
        ->and($second->ward_id)->toBe($ward->id)
        ->and($first->wardName())->toBe('Post-CCU');
});
