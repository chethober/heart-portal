<?php

use App\Enums\HospitalStatus;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;

test('platform admin can approve and suspend hospitals', function () {
    $admin = User::factory()->platformAdmin()->create();
    $hospital = Hospital::factory()->pending()->create();

    $this->actingAs($admin)->get('/dashboard/hospitals')->assertOk()->assertSee($hospital->name);

    $this->actingAs($admin)->patch("/dashboard/hospitals/{$hospital->id}/status", ['status' => 'active'])->assertRedirect();
    expect($hospital->fresh()->status)->toBe(HospitalStatus::Active)
        ->and($hospital->fresh()->approved_at)->not->toBeNull();

    $this->actingAs($admin)->patch("/dashboard/hospitals/{$hospital->id}/status", ['status' => 'suspended']);
    expect($hospital->fresh()->status)->toBe(HospitalStatus::Suspended);
});

test('hospital staff cannot reach platform admin pages', function () {
    $user = User::factory()->role(Role::HospitalAdmin)->create();
    $hospital = Hospital::factory()->pending()->create();

    $this->actingAs($user)->get('/dashboard/hospitals')->assertForbidden();
    $this->actingAs($user)->patch("/dashboard/hospitals/{$hospital->id}/status", ['status' => 'active'])->assertForbidden();
});

test('platform admin cannot use hospital routes', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)->get('/dashboard/staff')->assertForbidden();
    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.hospitals.index'));
});
