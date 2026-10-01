<?php

use App\Enums\HospitalStatus;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registering creates a pending hospital and its admin', function () {
    $response = $this->post('/register', [
        'hospital_name' => 'Afshar Hospital',
        'hospital_city' => 'Yazd',
        'hospital_phone' => '+98 35 0000 0000',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $hospital = Hospital::sole();
    expect($hospital->status)->toBe(HospitalStatus::Pending)
        ->and($hospital->slug)->toBe('afshar-hospital');

    $user = User::sole();
    expect($user->hospital_id)->toBe($hospital->id)
        ->and($user->hasRole(Role::HospitalAdmin->value))->toBeTrue();
});

test('hospital slugs stay unique', function () {
    Hospital::factory()->create(['slug' => 'afshar-hospital']);

    $this->post('/register', [
        'hospital_name' => 'Afshar Hospital',
        'hospital_city' => 'Yazd',
        'hospital_phone' => '123',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Hospital::where('slug', 'afshar-hospital-2')->exists())->toBeTrue();
});

test('users of a pending hospital are sent to the pending page', function () {
    $user = User::factory()->for(Hospital::factory()->pending())->role(Role::HospitalAdmin)->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('hospital.pending'));
    $this->actingAs($user)->get('/staff')->assertRedirect(route('hospital.pending'));
});
