<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

test('demo seeding creates the demo hospital and accounts without the default password', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->artisan('app:seed-demo')->assertSuccessful();

    $hospital = Hospital::where('slug', 'demo-heart-hospital')->sole();
    $doctor = User::where('email', 'doctor@echo.test')->sole();
    $admin = User::where('email', 'admin@echo.test')->sole();

    expect($doctor->hospital_id)->toBe($hospital->id)
        ->and($doctor->hasRole(Role::Doctor->value))->toBeTrue()
        ->and($admin->hospital_id)->toBeNull()
        ->and($admin->hasRole(Role::PlatformAdmin->value))->toBeTrue()
        ->and(Hash::check('password', $doctor->password))->toBeFalse();
});

test('running demo seeding again resets passwords without duplicating accounts', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->artisan('app:seed-demo')->assertSuccessful();
    $before = User::where('email', 'tech@echo.test')->value('password');

    $this->artisan('app:seed-demo')->assertSuccessful();

    expect(User::where('email', 'like', '%@echo.test')->count())->toBe(4)
        ->and(Hospital::where('slug', 'demo-heart-hospital')->count())->toBe(1)
        ->and(User::where('email', 'tech@echo.test')->value('password'))->not->toBe($before);
});
