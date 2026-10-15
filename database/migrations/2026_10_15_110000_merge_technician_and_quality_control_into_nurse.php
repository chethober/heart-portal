<?php

use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Echo technicians and quality control become nurses (Role::Nurse, "Nurse / technician").
 * Their form access is kept: both roles had the same form ceiling as nurses.
 */
return new class extends Migration
{
    private const OLD = ['technician', 'quality_control'];

    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        $nurse = SpatieRole::findByName(Role::Nurse->value, 'web');

        foreach (SpatieRole::whereIn('name', self::OLD)->get() as $old) {
            $pivot = config('permission.table_names.model_has_roles');

            // Members who already hold the nurse role only lose the old one.
            DB::table($pivot)->where('role_id', $old->id)
                ->whereExists(fn ($query) => $query->from("{$pivot} as held")
                    ->whereColumn('held.model_id', "{$pivot}.model_id")
                    ->whereColumn('held.model_type', "{$pivot}.model_type")
                    ->where('held.role_id', $nurse->id))
                ->delete();
            DB::table($pivot)->where('role_id', $old->id)->update(['role_id' => $nurse->id]);

            $old->delete();
        }

        DB::table('invitations')->whereIn('role', self::OLD)->update(['role' => Role::Nurse->value]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The merged members cannot be told apart again; nothing to undo.
    }
};
