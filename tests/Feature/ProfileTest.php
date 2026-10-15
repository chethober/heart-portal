<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/dashboard/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/dashboard/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/dashboard/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('the profile is organised into account, password, two-factor and, for signers, signature sections', function () {
    $hospital = Hospital::factory()->create();
    $doctor = User::factory()->for($hospital)->role(Role::Doctor)->create();
    $nurse = User::factory()->for($hospital)->role(Role::Nurse)->create();

    $this->actingAs($doctor)->get('/dashboard/profile')->assertOk()
        ->assertSee(['id="account"', 'id="password"', 'id="two-factor"', 'id="signature"', 'name="medical_license_no"', 'name="drawn_signature"'], false)
        ->assertSee($hospital->name);

    $this->actingAs($nurse)->get('/dashboard/profile')->assertOk()
        ->assertSee('id="password"', false)
        ->assertDontSee(['id="signature"', 'name="medical_license_no"'], false);
});

test('doctors set the license number printed under their signature, and the change is audited', function () {
    $doctor = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();

    $this->actingAs($doctor)->patch('/dashboard/profile', ['name' => $doctor->name, 'email' => $doctor->email, 'medical_license_no' => 'MC-12345'])
        ->assertSessionHasNoErrors();

    expect($doctor->fresh()->medical_license_no)->toBe('MC-12345')
        ->and(AuditLog::where('action', 'user.profile_updated')->sole()->changes)->toBe(['medical_license_no' => [null, 'MC-12345']]);

    $nurse = User::factory()->for($doctor->hospital)->role(Role::Nurse)->create();
    $this->actingAs($nurse)->patch('/dashboard/profile', ['name' => $nurse->name, 'email' => $nurse->email, 'medical_license_no' => 'X-1']);
    expect($nurse->fresh()->medical_license_no)->toBeNull();
});

test('signed-in users can email themselves a password reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/dashboard/profile/password-reset')
        ->assertRedirect(route('profile.edit').'#password')
        ->assertSessionHas('status', 'password-reset-link-sent');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('users cannot delete their own account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/dashboard/profile')->assertStatus(405);
    $this->assertNotNull($user->fresh());
});
