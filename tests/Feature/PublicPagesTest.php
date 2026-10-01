<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\TwoFactor;

test('landing page is public and links to registration, help and source', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('register'))
        ->assertSee(route('help'))
        ->assertSee(config('app.source_url'))
        ->assertSee('AGPL-3.0');
});

test('signed-in users get a dashboard link on the landing page', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertSee(route('dashboard'));
});

test('help center is public; the echo form guide requires login', function () {
    $this->get('/help')->assertOk()->assertSee('Signing and amending')->assertDontSee(route('help.form'));
    $this->get('/dashboard/help')->assertRedirect(route('login'));

    $tech = User::factory()->role(Role::Technician)->create();
    $this->actingAs($tech)->get('/help')->assertSee(route('help.form'));
    $this->actingAs($tech)->get('/dashboard/help')->assertOk();
});

test('every authenticated page offers the source code link', function () {
    $this->actingAs(User::factory()->role(Role::HospitalAdmin)->create())
        ->get('/dashboard')
        ->assertSee(config('app.source_url'));
});

test('hospital admins can reset a colleague\'s two-factor login', function () {
    $admin = User::factory()->role(Role::HospitalAdmin)->create();
    $doctor = User::factory()->for($admin->hospital)->role(Role::Doctor)->create();
    app(TwoFactor::class)->enable($doctor);
    $doctor->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->actingAs($admin)->get('/dashboard/staff')->assertSee('Reset 2FA');
    $this->actingAs($admin)->delete("/dashboard/staff/{$doctor->id}/two-factor")->assertRedirect();

    expect($doctor->fresh()->hasEnabledTwoFactor())->toBeFalse()
        ->and(AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'staff.two_factor_reset')->exists())->toBeTrue();

    $stranger = User::factory()->role(Role::HospitalAdmin)->create();
    $this->actingAs($stranger)->delete("/dashboard/staff/{$doctor->id}/two-factor")->assertNotFound();
});
