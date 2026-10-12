<?php

namespace App\Console\Commands;

use App\Enums\HospitalStatus;
use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:seed-demo')]
#[Description('Create (or reset) the demo hospital and accounts with fresh random passwords')]
class SeedDemo extends Command
{
    /**
     * @var array<string, array{0: string, 1: Role}>
     */
    private const ACCOUNTS = [
        'admin@echo.test' => ['Platform Admin', Role::PlatformAdmin],
        'hadmin@echo.test' => ['Hospital Admin', Role::HospitalAdmin],
        'doctor@echo.test' => ['Dr. Demo', Role::Doctor],
        'tech@echo.test' => ['Tech Demo', Role::Technician],
    ];

    public function handle(): int
    {
        $hospital = Hospital::firstOrCreate(
            ['slug' => 'demo-heart-hospital'],
            ['name' => 'Demo Heart Hospital', 'status' => HospitalStatus::Active, 'approved_at' => now()],
        );

        $rows = [];
        foreach (self::ACCOUNTS as $email => [$name, $role]) {
            $password = Str::password(20, symbols: false);

            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'name' => $name,
                'password' => $password,
                'hospital_id' => $role === Role::PlatformAdmin ? null : $hospital->id,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
            $user->syncRoles([$role->value]);

            $rows[] = [$email, $password, $role->label()];
        }

        $this->table(['Email', 'Password', 'Role'], $rows);
        $this->components->warn('These passwords are shown once. Run the command again to reset them.');

        return self::SUCCESS;
    }
}
