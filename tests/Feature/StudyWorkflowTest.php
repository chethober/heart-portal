<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr. Signer', 'medical_license_no' => 'M-12345']);

    $this->actingAs($this->tech)->postJson('/studies', echoPayload())->assertCreated();
    $this->study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
});

test('new studies are drafts and show as unsigned', function () {
    expect($this->study->status)->toBe(StudyStatus::Draft);

    $this->actingAs($this->tech)->get("/studies/{$this->study->id}")
        ->assertOk()
        ->assertSee('DRAFT — NOT SIGNED')
        ->assertSee('Edit Draft')
        ->assertDontSee('Sign &amp; Finalize', false);
});

test('clinical staff can edit drafts without a reason', function () {
    $this->actingAs($this->tech)->get("/studies/{$this->study->id}/edit")->assertOk()->assertSee('Editing draft');

    $this->actingAs($this->tech)
        ->putJson("/studies/{$this->study->id}", echoPayload(['lv_EF' => 45]))
        ->assertOk()
        ->assertJson(['url' => route('studies.show', $this->study)]);

    expect($this->study->fresh()->lv_EF)->toBe(45.0);

    $this->actingAs($this->tech)
        ->putJson("/studies/{$this->study->id}", echoPayload(['amendment_reason' => 'not needed']))
        ->assertJsonValidationErrors(['amendment_reason']);
});

test('only doctors can sign, and signing records the signer and version 1', function () {
    $this->actingAs($this->tech)->post("/studies/{$this->study->id}/finalize")->assertForbidden();

    $this->actingAs($this->doctor)->post("/studies/{$this->study->id}/finalize")
        ->assertRedirect(route('studies.show', $this->study));

    $study = $this->study->fresh();
    expect($study->status)->toBe(StudyStatus::Final)
        ->and($study->reported_by)->toBe($this->doctor->id)
        ->and($study->doctor)->toBe('Dr. Signer')
        ->and($study->finalized_at)->not->toBeNull()
        ->and($study->versions()->count())->toBe(1);

    $this->actingAs($this->tech)->get("/studies/{$this->study->id}")
        ->assertOk()
        ->assertDontSee('DRAFT — NOT SIGNED')
        ->assertSee('Dr. Signer')
        ->assertSee('M-12345');

    // Signing twice is not possible.
    $this->actingAs($this->doctor)->post("/studies/{$this->study->id}/finalize")->assertForbidden();
});

test('signed reports are locked and can only be amended by a doctor with a reason', function () {
    $this->actingAs($this->doctor)->post("/studies/{$this->study->id}/finalize");

    $this->actingAs($this->tech)->get("/studies/{$this->study->id}/edit")->assertForbidden();
    $this->actingAs($this->tech)->putJson("/studies/{$this->study->id}", echoPayload(['lv_EF' => 20]))->assertForbidden();
    $this->actingAs($this->tech)->delete("/studies/{$this->study->id}")->assertForbidden();
    $this->actingAs($this->doctor)->delete("/studies/{$this->study->id}")->assertForbidden();

    $this->actingAs($this->doctor)->putJson("/studies/{$this->study->id}", echoPayload(['lv_EF' => 20]))
        ->assertJsonValidationErrors(['amendment_reason']);

    $this->actingAs($this->doctor)->get("/studies/{$this->study->id}/edit")->assertOk()->assertSee('Reason for amendment');
    $this->actingAs($this->doctor)
        ->putJson("/studies/{$this->study->id}", echoPayload(['lv_EF' => 20, 'amendment_reason' => 'EF re-measured on review']))
        ->assertOk();

    $study = $this->study->fresh();
    expect($study->status)->toBe(StudyStatus::Amended)
        ->and($study->lv_EF)->toBe(20.0)
        ->and($study->amended_by)->toBe($this->doctor->id)
        ->and($study->versions()->pluck('version')->all())->toBe([1, 2]);

    // The original signed version is preserved unchanged.
    $first = $study->versions()->first();
    expect($first->data['lv_EF'])->toBe('35')
        ->and($first->status)->toBe(StudyStatus::Final);

    $this->actingAs($this->tech)->get("/studies/{$this->study->id}")
        ->assertSee('EF re-measured on review');
    $this->actingAs($this->tech)->get("/studies/{$this->study->id}?version=1")
        ->assertOk()
        ->assertSee('Showing signed version 1 of 2')
        ->assertSeeText('EF = 35%');
    $this->actingAs($this->tech)->get("/studies/{$this->study->id}?version=9")->assertNotFound();
});

test('drafts can be deleted by their author or a hospital admin only', function () {
    $otherTech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
    $this->actingAs($otherTech)->delete("/studies/{$this->study->id}")->assertForbidden();
    $this->actingAs($this->doctor)->delete("/studies/{$this->study->id}")->assertForbidden();

    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->actingAs($admin)->delete("/studies/{$this->study->id}")->assertRedirect();

    expect($this->study->fresh()->trashed())->toBeTrue();
});

test('history shows status and edit links', function () {
    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertSee('Draft')
        ->assertSee(route('studies.edit', $this->study), false);

    $this->actingAs($this->doctor)->post("/studies/{$this->study->id}/finalize");

    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertSee('Final')
        ->assertDontSee(route('studies.edit', $this->study), false);
    $this->actingAs($this->doctor)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertSee('Amend');
});
