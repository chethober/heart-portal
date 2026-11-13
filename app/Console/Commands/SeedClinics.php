<?php

namespace App\Console\Commands;

use App\Enums\HospitalStatus;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('app:seed-clinics {logos? : Path to a checkout of the legacy echo repo; each clinic\'s logos are read from its folder}')]
#[Description('Create (or reset) the legacy echo clinics as demo hospitals, each with admin, doctor and nurse accounts and its report logos')]
class SeedClinics extends Command
{
    /**
     * The clinics of the legacy echo repo. Logos are files in the clinic's folder there: `logo` is
     * printed on the right of the report header and `secondary` on the left, as in echo.
     *
     * @var array<string, array{name: string, folder: string, logo: string, secondary: string}>
     */
    private const CLINICS = [
        'afshar' => ['name' => 'بیمارستان افشار', 'folder' => 'afshar', 'logo' => 'afshar.png', 'secondary' => 'ssu.png'],
        'shahedieh' => ['name' => 'درمانگاه امام حسین شاهدیه', 'folder' => 'shahedie', 'logo' => 'shahedieh.png', 'secondary' => 'shahedieh.png'],
        'seyedshohada' => ['name' => 'بیمارستان سیدالشهدا', 'folder' => 'seyedsh', 'logo' => 'seyedsh2.png', 'secondary' => 'seyedsh1.png'],
    ];

    /**
     * @var array<string, array{0: string, 1: Role}>
     */
    private const ACCOUNTS = [
        'hadmin' => ['Hospital Admin', Role::HospitalAdmin],
        'doctor' => ['Dr. Demo', Role::Doctor],
        'nurse' => ['Nurse Demo', Role::Nurse],
    ];

    public function handle(): int
    {
        $source = $this->argument('logos');
        if (is_string($source) && ! is_dir($source)) {
            $this->components->error("Logo folder not found: {$source}");

            return self::FAILURE;
        }

        $rows = [];
        foreach (self::CLINICS as $slug => $clinic) {
            $hospital = Hospital::firstOrCreate(
                ['slug' => $slug],
                ['name' => $clinic['name'], 'status' => HospitalStatus::Active, 'approved_at' => now()],
            );

            if (is_string($source)) {
                $this->storeLogos($hospital, $source.'/'.$clinic['folder'], $clinic['logo'], $clinic['secondary']);
            }

            foreach (self::ACCOUNTS as $local => [$name, $role]) {
                $email = "{$local}@{$slug}.echo.test";
                $password = Str::password(20, symbols: false);

                $user = User::firstOrNew(['email' => $email]);
                $user->fill(['name' => $name, 'password' => $password, 'hospital_id' => $hospital->id, 'is_active' => true]);
                $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
                $user->assignStaffRole($role);

                $rows[] = [$clinic['name'], $email, $password, $role->label()];
            }
        }

        $this->table(['Clinic', 'Email', 'Password', 'Role'], $rows);
        $this->components->warn('These passwords are shown once. Run the command again to reset them.');

        return self::SUCCESS;
    }

    private function storeLogos(Hospital $hospital, string $folder, string $logo, string $secondary): void
    {
        $paths = [];
        foreach (['logo' => $logo, 'secondary-logo' => $secondary] as $key => $file) {
            $from = $folder.'/'.$file;
            if (! is_file($from)) {
                $this->components->warn("Missing logo, skipped: {$from}");

                continue;
            }

            $paths[$key] = "hospitals/{$hospital->slug}/{$key}.".pathinfo($file, PATHINFO_EXTENSION);
            Storage::disk('public')->put($paths[$key], (string) file_get_contents($from));
        }

        if (isset($paths['logo'])) {
            $hospital->logo_path = $paths['logo'];
        }
        if (isset($paths['secondary-logo'])) {
            $hospital->settings = [...$hospital->settings ?? [], 'secondary_logo_path' => $paths['secondary-logo']];
        }
        $hospital->save();
    }
}
