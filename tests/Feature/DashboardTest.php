<?php

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();

    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $this->study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
});

test('the dashboard home is the worklist with actions', function () {
    $this->actingAs($this->tech)->get('/dashboard')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee('Draft')
        ->assertSee(route('studies.create'), false)
        ->assertSee(route('studies.show', $this->study), false)
        ->assertSee(route('studies.edit', $this->study), false)
        ->assertSee(route('studies.destroy', $this->study), false)
        ->assertViewHas('counts', fn ($counts) => $counts->all() === ['draft' => 1]);
});

test('the worklist filters by status and patient name', function () {
    $this->actingAs($this->tech)->get('/dashboard?status=final')->assertOk()->assertDontSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard?status=draft')->assertSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard?search=reza')->assertSee('Rezaei')->assertSee('Clear filters');
    $this->actingAs($this->tech)->get('/dashboard?search=nobody')->assertDontSee('Rezaei');
});

test('doctors can sign drafts from the worklist', function () {
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    $this->actingAs($this->tech)->get('/dashboard')->assertDontSee(route('studies.finalize', $this->study), false);
    $this->actingAs($doctor)->get('/dashboard')->assertSee(route('studies.finalize', $this->study), false);
});

test('the patient echos section searches by national ID', function () {
    $this->actingAs($this->tech)->get('/dashboard/patients')->assertOk()->assertDontSee('Rezaei');

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee(route('studies.show', $this->study), false);

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=999')
        ->assertOk()
        ->assertSee('No echo reports found')
        ->assertSee('Record a new echo study');
});

test('tabs only offer the sections a role may open', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $platformAdmin = User::factory()->platformAdmin()->create();

    $this->actingAs($this->tech)->get('/dashboard/patients')
        ->assertSee(route('dashboard'), false)
        ->assertDontSee(route('staff.index'), false)
        ->assertDontSee(route('audit.index'), false);

    $this->actingAs($admin)->get('/dashboard/staff')
        ->assertOk()
        ->assertSee(route('patients.index'), false)
        ->assertSee(route('audit.index'), false)
        ->assertDontSee(route('studies.create'), false);

    $this->actingAs($platformAdmin)->get('/dashboard')->assertRedirect(route('admin.hospitals.index'));
    $this->actingAs($platformAdmin)->get('/dashboard/hospitals')->assertOk()->assertDontSee(route('patients.index'), false);
});

test('old URLs redirect to their dashboard section', function (string $from, string $to) {
    $this->actingAs($this->tech)->get($from)->assertRedirect($to)->assertStatus(301);
})->with([
    ['/patients?nationalID=0012345678', '/dashboard/patients?nationalID=0012345678'],
    ['/studies/create', '/dashboard/studies/create'],
    ['/studies/7/edit', '/dashboard/studies/7/edit'],
    ['/staff', '/dashboard/staff'],
    ['/audit?action=study.viewed', '/dashboard/audit?action=study.viewed'],
    ['/admin/hospitals', '/dashboard/hospitals'],
    ['/help/echo-form', '/dashboard/help'],
    ['/profile', '/dashboard/profile'],
]);

test('the old patients tab link redirects to the patient echos section', function () {
    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertRedirect(route('patients.index', ['nationalID' => '0012345678']));
});

test('deleting a draft returns to the section it was deleted from', function () {
    $this->actingAs($this->tech)->delete("/dashboard/studies/{$this->study->id}", ['tab' => 'studies'])
        ->assertRedirect(route('dashboard'));

    $second = $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->json('id');

    $this->actingAs($this->tech)->delete("/dashboard/studies/{$second}")
        ->assertRedirect(route('patients.index', ['nationalID' => '0012345678']));
});

test('another hospital sees none of these studies on its dashboard', function () {
    $intruder = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($intruder)->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Rezaei')
        ->assertSee('No studies found')
        ->assertViewHas('counts', fn ($counts) => $counts->isEmpty());
    $this->actingAs($intruder)->get('/dashboard?search=Rezaei')->assertDontSee(route('studies.show', $this->study), false);
    $this->actingAs($intruder)->get('/dashboard/patients?nationalID=0012345678')->assertDontSee('Rezaei');
});

test('deactivated staff are logged out instead of seeing patient data', function () {
    $this->tech->forceFill(['is_active' => false])->save();

    $this->actingAs($this->tech)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});
