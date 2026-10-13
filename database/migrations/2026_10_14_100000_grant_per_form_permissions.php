<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Replaces the shared studies.create/edit/finalize permissions with per-form ones (App\Support\Forms).
 * Every existing member is granted their role's default form access, so nobody's access changes.
 */
return new class extends Migration
{
    private const OLD = ['studies.create', 'studies.edit', 'studies.finalize'];

    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        User::query()->with('roles')->lazyById()->each(function (User $user) {
            $defaults = $user->getRoleNames()
                ->flatMap(fn (string $name) => Role::tryFrom($name)?->defaultFormPermissions() ?? [])
                ->unique()->values()->all();

            if ($defaults !== []) {
                $user->givePermissionTo($defaults);
            }
        });

        Permission::whereIn('name', self::OLD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::OLD as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
