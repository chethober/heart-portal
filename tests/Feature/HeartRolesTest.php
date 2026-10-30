<?php

use App\Enums\Role;
use App\Models\AngiographyStudy;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
});

test('nurses record and edit drafts but cannot sign or see the audit log', function () {
    $nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $study = AngiographyStudy::factory()->for(Patient::factory()->for($this->hospital))->create(['created_by' => $nurse->id]);

    $this->actingAs($nurse)->get('/dashboard/procedures/angiography/create')->assertRedirect(route('dashboard'));
    $this->actingAs($nurse)->get("/dashboard/procedures/angiography/{$study->id}/edit")->assertOk();
    $this->actingAs($nurse)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertForbidden();
    $this->actingAs($nurse)->get('/dashboard/audit')->assertForbidden();
});

test('hospital admins can invite and assign the nurse / technician role', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    $this->actingAs($admin)->get('/dashboard/staff')->assertOk()->assertSee('Nurse / technician')->assertDontSee('Quality control');
    $this->actingAs($admin)->post('/dashboard/staff/invitations', ['email' => 'nurse@example.com', 'role' => 'nurse'])->assertRedirect();
    $this->actingAs($admin)->patch("/dashboard/staff/{$doctor->id}", ['role' => 'nurse', 'is_active' => 1])->assertRedirect();

    expect(Invitation::withoutGlobalScopes()->sole()->role)->toBe(Role::Nurse)
        ->and($doctor->fresh()->hasRole('nurse'))->toBeTrue();
});

test('the removed technician and quality control roles cannot be assigned', function (string $role) {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->post('/dashboard/staff/invitations', ['email' => 'x@example.com', 'role' => $role])->assertSessionHasErrors('role');
})->with(['technician', 'quality_control']);

test('existing technicians, quality control and their invitations become nurses', function () {
    $tech = User::factory()->for($this->hospital)->create();
    $qc = User::factory()->for($this->hospital)->create();
    $both = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    foreach ([[$tech, 'technician'], [$qc, 'quality_control'], [$both, 'technician']] as [$user, $role]) {
        $user->assignRole(SpatieRole::findOrCreate($role, 'web'));
    }
    DB::table('invitations')->insert(['hospital_id' => $this->hospital->id, 'invited_by' => $both->id, 'email' => 'qc@example.com', 'role' => 'quality_control', 'token' => str_repeat('a', 64), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);

    (require database_path('migrations/2026_10_15_110000_merge_technician_and_quality_control_into_nurse.php'))->up();

    foreach ([$tech, $qc, $both] as $user) {
        expect($user->fresh()->getRoleNames()->all())->toBe(['nurse']);
    }
    expect(SpatieRole::whereIn('name', ['technician', 'quality_control'])->exists())->toBeFalse()
        ->and(Invitation::withoutGlobalScopes()->sole()->role)->toBe(Role::Nurse);
});
