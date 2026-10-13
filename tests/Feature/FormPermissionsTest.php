<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\Patient;
use App\Models\User;
use App\Support\Forms;
use App\Support\Procedures;
use Illuminate\Support\Str;

dataset('procedures', array_keys(Procedures::MODELS));

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->patient = Patient::factory()->for($this->hospital)->create();
});

test('each role starts with its default form access', function () {
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    expect($doctor->can('angiography.finalize'))->toBeTrue()
        ->and($this->nurse->can('angiography.create'))->toBeTrue()
        ->and($this->nurse->can('angiography.finalize'))->toBeFalse()
        ->and($this->admin->can('echo.view'))->toBeTrue()
        ->and($this->admin->can('echo.create'))->toBeFalse()
        ->and($this->nurse->can('admission.finalize'))->toBeFalse();
});

test('a member without a procedure cannot open, record or list it', function (string $procedure) {
    $this->nurse->revokePermissionTo([Forms::permission($procedure, 'view'), Forms::permission($procedure, 'create')]);
    $model = Procedures::model($procedure);
    $study = $model::query()->forceCreate(['hospital_id' => $this->hospital->id, 'patient_id' => $this->patient->id, 'created_by' => $this->nurse->id]);

    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/{$study->id}")->assertForbidden();
    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/create")->assertForbidden();
    $this->actingAs($this->nurse)->post("/dashboard/procedures/{$procedure}", ['nationalID' => $this->patient->national_id])->assertForbidden();
    $this->actingAs($this->nurse)->get('/dashboard/worklist')->assertOk()
        ->assertDontSee(route('procedures.create', $procedure), false)
        ->assertDontSee(route('procedures.show', [$procedure, $study]), false);
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID='.$this->patient->national_id)->assertOk()
        ->assertDontSee(route('procedures.show', [$procedure, $study]), false);

    // The other procedures are unaffected.
    $other = collect(array_keys(Procedures::MODELS))->first(fn (string $key) => $key !== $procedure);
    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$other}/create")->assertOk();
})->with('procedures');

test('signing needs the form\'s finalize permission', function () {
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
    $doctor->revokePermissionTo('angiography.finalize');
    $study = AngiographyStudy::factory()->for($this->patient)->create();

    $this->actingAs($doctor)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertForbidden();
    expect($doctor->can('sign-reports'))->toBeTrue();
});

test('echo access is its own permission', function () {
    $this->nurse->revokePermissionTo(['echo.view', 'echo.create']);
    $study = EchoStudy::factory()->for($this->patient)->create();

    $this->actingAs($this->nurse)->get("/dashboard/studies/{$study->id}")->assertForbidden();
    $this->actingAs($this->nurse)->get('/dashboard/studies/create')->assertForbidden();
    // The dashboard home is the echo form only for those who may record echo.
    $this->actingAs($this->nurse)->get('/dashboard')->assertRedirect(route('worklist'));
});

test('admissions are guarded by the admission permissions', function () {
    $admission = Admission::factory()->for($this->patient)->create();
    $this->nurse->revokePermissionTo(['admission.create', 'admission.edit']);

    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/admissions/{$admission->id}")->assertOk();
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/admissions/create")->assertForbidden();
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/admissions/{$admission->id}/edit")->assertForbidden();

    $this->nurse->revokePermissionTo('admission.view');
    $this->actingAs($this->nurse)->get("/dashboard/patients/{$this->patient->id}/admissions/{$admission->id}")->assertForbidden();
});

test('hospital admins set a member\'s form access within the role\'s ceiling', function () {
    $this->actingAs($this->admin)->get('/dashboard/staff')->assertOk()->assertSee('Form access');

    $this->actingAs($this->admin)->put("/dashboard/staff/{$this->nurse->id}/access", ['permissions' => ['echo.view', 'echo.create']])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($this->nurse->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe(['echo.create', 'echo.view']);

    // Nurses never sign, so the grid cannot grant it.
    $this->actingAs($this->admin)->put("/dashboard/staff/{$this->nurse->id}/access", ['permissions' => ['echo.finalize']])
        ->assertSessionHasErrors('permissions.0');
    expect($this->nurse->fresh()->can('echo.finalize'))->toBeFalse();
});

test('another hospital\'s admin cannot change a member\'s access', function () {
    $otherAdmin = User::factory()->for(Hospital::factory())->role(Role::HospitalAdmin)->create();

    $this->actingAs($otherAdmin)->put("/dashboard/staff/{$this->nurse->id}/access", ['permissions' => []])->assertNotFound();
    expect($this->nurse->fresh()->can('angiography.create'))->toBeTrue();
});

test('a role change resets form access to the new role\'s defaults', function () {
    $this->nurse->syncPermissions(['echo.view']);

    $this->actingAs($this->admin)->patch("/dashboard/staff/{$this->nurse->id}", ['role' => 'doctor', 'is_active' => 1])->assertRedirect();

    expect($this->nurse->fresh()->can('angiography.finalize'))->toBeTrue();
});

test('accepted invitations come with the role\'s default access', function () {
    $token = Str::random(40);
    Invitation::withoutGlobalScopes()->create([
        'hospital_id' => $this->hospital->id,
        'invited_by' => $this->admin->id,
        'email' => 'new.nurse@example.com',
        'role' => Role::Nurse,
        'token' => $token,
        'expires_at' => now()->addDay(),
    ]);

    $this->post("/invitations/{$token}", ['name' => 'New Nurse', 'password' => 'a-long-Passw0rd!', 'password_confirmation' => 'a-long-Passw0rd!'])
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'new.nurse@example.com')->sole();
    expect($user->can('discharge.edit'))->toBeTrue()
        ->and($user->can('discharge.finalize'))->toBeFalse();
});
