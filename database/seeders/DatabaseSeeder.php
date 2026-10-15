<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DrugCatalogSeeder::class);

        if (! app()->environment('local')) {
            return;
        }

        // Demo accounts for local development only; all passwords are "password".
        User::factory()->platformAdmin()->create(['name' => 'Platform Admin', 'email' => 'admin@echo.test']);

        $hospital = Hospital::factory()->create(['name' => 'Demo Heart Hospital', 'slug' => 'demo-heart-hospital']);

        User::factory()->for($hospital)->role(Role::HospitalAdmin)->create(['name' => 'Hospital Admin', 'email' => 'hadmin@echo.test']);
        User::factory()->for($hospital)->role(Role::Doctor)->create(['name' => 'Dr. Demo', 'email' => 'doctor@echo.test']);
        User::factory()->for($hospital)->role(Role::Nurse)->create(['name' => 'Nurse Demo', 'email' => 'nurse@echo.test']);

        // Patients, admissions and angiographies in the heart system's fields.
        (new HeartDemoSeeder)->run($hospital);
    }
}
