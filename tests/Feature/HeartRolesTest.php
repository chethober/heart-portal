<?php

use App\Enums\Role;
use App\Models\AngiographyStudy;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\Patient;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
});

test('nurses record and edit drafts but cannot sign or see the audit log', function () {
    $nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $study = AngiographyStudy::factory()->for(Patient::factory()->for($this->hospital))->create(['created_by' => $nurse->id]);

    $this->actingAs($nurse)->get('/dashboard/procedures/angiography/create')->assertOk();
    $this->actingAs($nurse)->get("/dashboard/procedures/angiography/{$study->id}/edit")->assertOk();
    $this->actingAs($nurse)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertForbidden();
    $this->actingAs($nurse)->get('/dashboard/audit')->assertForbidden();
});

test('quality control edits drafts and reviews the audit log but cannot sign', function () {
    $qc = User::factory()->for($this->hospital)->role(Role::QualityControl)->create();
    $study = AngiographyStudy::factory()->for(Patient::factory()->for($this->hospital))->create();

    $this->actingAs($qc)->get("/dashboard/procedures/angiography/{$study->id}/edit")->assertOk();
    $this->actingAs($qc)->get('/dashboard/audit')->assertOk();
    $this->actingAs($qc)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertForbidden();
    $this->actingAs($qc)->get('/dashboard/staff')->assertForbidden();
});

test('hospital admins can invite and assign the heart roles', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $nurse = User::factory()->for($this->hospital)->role(Role::Technician)->create();

    $this->actingAs($admin)->get('/dashboard/staff')->assertOk()->assertSee('Nurse')->assertSee('Quality control');
    $this->actingAs($admin)->post('/dashboard/staff/invitations', ['email' => 'qc@example.com', 'role' => 'quality_control'])->assertRedirect();
    $this->actingAs($admin)->patch("/dashboard/staff/{$nurse->id}", ['role' => 'nurse', 'is_active' => 1])->assertRedirect();

    expect(Invitation::withoutGlobalScopes()->sole()->role)->toBe(Role::QualityControl)
        ->and($nurse->fresh()->hasRole('nurse'))->toBeTrue();
});
