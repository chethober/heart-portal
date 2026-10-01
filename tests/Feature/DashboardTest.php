<?php

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();

    $this->actingAs($this->tech)->postJson('/studies', echoPayload())->assertCreated();
    $this->study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
});

test('the studies tab is the default and lists the hospital worklist with actions', function () {
    $this->actingAs($this->tech)->get('/dashboard')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee('Draft')
        ->assertSee(route('studies.create'), false)
        ->assertSee(route('studies.show', $this->study), false)
        ->assertSee(route('studies.edit', $this->study), false)
        ->assertSee(route('studies.destroy', $this->study), false);
});

test('the studies tab filters by status and patient name', function () {
    $this->actingAs($this->tech)->get('/dashboard?tab=studies&status=final')->assertOk()->assertDontSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard?tab=studies&status=draft')->assertSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard?tab=studies&search=reza')->assertSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard?tab=studies&search=nobody')->assertDontSee('Rezaei');
});

test('doctors can sign drafts from the dashboard', function () {
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    $this->actingAs($this->tech)->get('/dashboard')->assertDontSee(route('studies.finalize', $this->study), false);
    $this->actingAs($doctor)->get('/dashboard')->assertSee(route('studies.finalize', $this->study), false);
});

test('the patients tab searches by national ID', function () {
    $this->actingAs($this->tech)->get('/dashboard?tab=patients')->assertOk()->assertDontSee('Rezaei');

    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee(route('studies.show', $this->study), false);

    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=999')
        ->assertOk()
        ->assertSee('No echo reports found');
});

test('the old patients page redirects to the patients tab', function () {
    $this->actingAs($this->tech)->get('/patients?nationalID=0012345678')
        ->assertRedirect(route('dashboard', ['tab' => 'patients', 'nationalID' => '0012345678']));
});

test('deleting a draft returns to the tab it was deleted from', function () {
    $this->actingAs($this->tech)->delete("/studies/{$this->study->id}", ['tab' => 'studies'])
        ->assertRedirect(route('dashboard', ['tab' => 'studies']));

    $second = $this->actingAs($this->tech)->postJson('/studies', echoPayload())->json('id');

    $this->actingAs($this->tech)->delete("/studies/{$second}")
        ->assertRedirect(route('dashboard', ['tab' => 'patients', 'nationalID' => '0012345678']));
});

test('another hospital sees none of these studies on its dashboard', function () {
    $intruder = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($intruder)->get('/dashboard')->assertOk()->assertDontSee('Rezaei')->assertSee('No studies found');
    $this->actingAs($intruder)->get('/dashboard?tab=studies&search=Rezaei')->assertDontSee(route('studies.show', $this->study), false);
    $this->actingAs($intruder)->get('/dashboard?tab=patients&nationalID=0012345678')->assertDontSee('Rezaei');
});

test('deactivated staff are logged out instead of seeing patient data', function () {
    $this->tech->forceFill(['is_active' => false])->save();

    $this->actingAs($this->tech)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});
