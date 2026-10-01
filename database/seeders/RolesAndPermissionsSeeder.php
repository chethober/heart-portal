<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to re-run after changing Role::permissions().
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $roleEnum) {
            $permissions = collect($roleEnum->permissions())
                ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

            Role::findOrCreate($roleEnum->value, 'web')->syncPermissions($permissions);
        }
    }
}
