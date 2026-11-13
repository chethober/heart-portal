<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function echoRepoWithLogos(): string
{
    $root = sys_get_temp_dir().'/echo-'.uniqid();
    foreach (['afshar' => ['afshar.png', 'ssu.png'], 'shahedie' => ['shahedieh.png'], 'seyedsh' => ['seyedsh1.png', 'seyedsh2.png']] as $folder => $files) {
        mkdir("{$root}/{$folder}", recursive: true);
        foreach ($files as $file) {
            $image = UploadedFile::fake()->image($file);
            copy($image->getPathname(), "{$root}/{$folder}/{$file}");
        }
    }

    return $root;
}

test('clinic seeding creates each echo clinic with staff accounts and report logos', function () {
    Storage::fake('public');
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->artisan('app:seed-clinics', ['logos' => echoRepoWithLogos()])->assertSuccessful();

    $afshar = Hospital::where('slug', 'afshar')->sole();
    $doctor = User::where('email', 'doctor@afshar.echo.test')->sole();

    expect(Hospital::whereIn('slug', ['afshar', 'shahedieh', 'seyedshohada'])->count())->toBe(3)
        ->and($afshar->isActive())->toBeTrue()
        ->and($afshar->logo_path)->toBe('hospitals/afshar/logo.png')
        ->and($afshar->settings['secondary_logo_path'] ?? null)->toBe('hospitals/afshar/secondary-logo.png')
        ->and($doctor->hospital_id)->toBe($afshar->id)
        ->and($doctor->hasRole(Role::Doctor->value))->toBeTrue()
        ->and(Hash::check('password', $doctor->password))->toBeFalse()
        ->and(User::where('email', 'hadmin@seyedshohada.echo.test')->sole()->hasRole(Role::HospitalAdmin->value))->toBeTrue();

    Storage::disk('public')->assertExists(['hospitals/afshar/logo.png', 'hospitals/shahedieh/secondary-logo.png', 'hospitals/seyedshohada/logo.png']);
});

test('running clinic seeding again resets passwords without duplicating anything', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->artisan('app:seed-clinics')->assertSuccessful();
    $before = User::where('email', 'nurse@shahedieh.echo.test')->value('password');

    $this->artisan('app:seed-clinics')->assertSuccessful();

    expect(User::where('email', 'like', '%.echo.test')->count())->toBe(9)
        ->and(Hospital::count())->toBe(3)
        ->and(Hospital::where('slug', 'afshar')->value('logo_path'))->toBeNull()
        ->and(User::where('email', 'nurse@shahedieh.echo.test')->value('password'))->not->toBe($before);
});

test('clinic seeding fails on a missing logo folder', function () {
    $this->artisan('app:seed-clinics', ['logos' => '/nonexistent/echo'])->assertFailed();

    expect(Hospital::count())->toBe(0);
});
