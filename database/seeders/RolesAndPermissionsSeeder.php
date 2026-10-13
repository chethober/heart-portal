<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Support\Forms;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to re-run after changing Role::permissions() or the forms in App\Support\Forms.
 * Form permissions are created here but granted per user (User::assignStaffRole()).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Forms::permissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            $permissions = collect($roleEnum->permissions())
                ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

            Role::findOrCreate($roleEnum->value, 'web')->syncPermissions($permissions);
        }
    }
}
