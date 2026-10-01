<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\Invitation;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
});

test('hospital admin can invite staff and the invitee can join', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post('/staff/invitations', ['email' => 'doc@example.com', 'role' => 'doctor'])
        ->assertRedirect();

    $invitation = Invitation::withoutGlobalScope(HospitalScope::class)->sole();
    expect($invitation->hospital_id)->toBe($this->hospital->id);
    Notification::assertSentOnDemand(StaffInvitation::class);

    auth()->logout();

    $this->get("/invitations/{$invitation->token}")->assertOk()->assertSee($this->hospital->name);
    $this->post("/invitations/{$invitation->token}", [
        'name' => 'Dr. Who',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $doctor = User::where('email', 'doc@example.com')->sole();
    expect($doctor->hospital_id)->toBe($this->hospital->id)
        ->and($doctor->hasRole('doctor'))->toBeTrue()
        ->and($doctor->hasVerifiedEmail())->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();

    // A used token cannot be replayed.
    auth()->logout();
    $this->get("/invitations/{$invitation->token}")->assertStatus(410);
});

test('hospital admins cannot grant the platform admin role', function () {
    $this->actingAs($this->admin)
        ->post('/staff/invitations', ['email' => 'x@example.com', 'role' => 'platform_admin'])
        ->assertSessionHasErrors('role');
});

test('doctors and technicians cannot manage staff', function (Role $role) {
    $user = User::factory()->for($this->hospital)->role($role)->create();

    $this->actingAs($user)->get('/staff')->assertForbidden();
    $this->actingAs($user)->post('/staff/invitations', ['email' => 'x@example.com', 'role' => 'doctor'])->assertForbidden();
})->with([Role::Doctor, Role::Technician]);

test('hospital admin only sees and edits their own staff and invitations', function () {
    $otherHospital = Hospital::factory()->create();
    $otherAdmin = User::factory()->for($otherHospital)->role(Role::HospitalAdmin)->create();
    $otherDoctor = User::factory()->for($otherHospital)->role(Role::Doctor)->create();
    $otherInvite = Invitation::create([
        'hospital_id' => $otherHospital->id,
        'invited_by' => $otherAdmin->id,
        'email' => 'secret@example.com',
        'role' => 'doctor',
        'token' => str_repeat('a', 64),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($this->admin)->get('/staff')
        ->assertOk()
        ->assertDontSee($otherDoctor->email)
        ->assertDontSee('secret@example.com');

    $this->actingAs($this->admin)
        ->patch("/staff/{$otherDoctor->id}", ['role' => 'technician', 'is_active' => 0])
        ->assertNotFound();
    $this->actingAs($this->admin)->delete("/staff/invitations/{$otherInvite->id}")->assertNotFound();

    expect($otherDoctor->fresh()->is_active)->toBeTrue();
});

test('deactivated staff are logged out and cannot log in', function () {
    $tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();

    $this->actingAs($this->admin)
        ->patch("/staff/{$tech->id}", ['role' => 'technician', 'is_active' => 0])
        ->assertRedirect();
    expect($tech->fresh()->is_active)->toBeFalse();

    auth()->logout();
    $this->post('/login', ['email' => $tech->email, 'password' => 'password']);
    $this->assertGuest();
});

test('admin cannot demote or deactivate themselves', function () {
    $this->actingAs($this->admin)
        ->patch("/staff/{$this->admin->id}", ['role' => 'doctor', 'is_active' => 1])
        ->assertSessionHasErrors('staff');

    expect($this->admin->fresh()->hasRole('hospital_admin'))->toBeTrue();
});

test('tenant scope hides other hospitals rows and fails closed without context', function () {
    Invitation::create([
        'hospital_id' => $this->hospital->id,
        'invited_by' => $this->admin->id,
        'email' => 'a@example.com',
        'role' => 'doctor',
        'token' => str_repeat('b', 64),
        'expires_at' => now()->addDay(),
    ]);

    expect(Invitation::count())->toBe(0);

    $this->actingAs($this->admin);
    expect(Invitation::count())->toBe(1);

    $this->actingAs(User::factory()->role(Role::HospitalAdmin)->create());
    expect(Invitation::count())->toBe(0);
});
