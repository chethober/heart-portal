<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\AngioplastyStudy;
use App\Models\DischargeStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\CurrentHospital;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('heart demo data needs the demo hospital', function () {
    $this->artisan('app:seed-heart-demo')->assertFailed();

    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('heart demo data fills only the demo hospital, once', function () {
    $hospital = Hospital::factory()->create(['slug' => 'demo-heart-hospital']);
    $other = Hospital::factory()->create();
    $doctor = User::factory()->for($hospital)->role(Role::Doctor)->create();
    User::factory()->for($hospital)->role(Role::Technician)->create();

    $this->artisan('app:seed-heart-demo')->assertSuccessful();
    $this->artisan('app:seed-heart-demo')->assertSuccessful();

    // Read back as the demo hospital; its signed versions are tenant-scoped too.
    app(CurrentHospital::class)->set($hospital->id);
    $studies = AngiographyStudy::with('versions')->get();

    expect($studies)->toHaveCount(6)
        ->and(AngiographyStudy::withoutGlobalScope(HospitalScope::class)->count())->toBe(6)
        ->and(Admission::withoutGlobalScope(HospitalScope::class)->count())->toBe(6)
        ->and(Patient::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $other->id)->count())->toBe(0)
        ->and($studies->countBy(fn ($s) => $s->status->value)->all())->toEqual(['final' => 2, 'amended' => 1, 'draft' => 3])
        ->and($studies->firstWhere('status', StudyStatus::Amended)->versions)->toHaveCount(2)
        ->and($studies->firstWhere('status', StudyStatus::Final)->reported_by)->toBe($doctor->id)
        ->and(AngioplastyStudy::count())->toBe(2)
        ->and(AngioplastyStudy::where('status', 'final')->count())->toBe(1)
        ->and(DischargeStudy::sole()->status)->toBe(StudyStatus::Draft);

    // The demo studies open like any other, and show heart's labels for the recorded codes.
    $study = $studies->firstWhere('lad_prox_Stenotic', '4');
    $this->actingAs($doctor)->get("/dashboard/procedures/angiography/{$study->id}")
        ->assertOk()
        ->assertSee('very severe stenosis(90-99%)')
        ->assertSee('Hossein Karimi');
});

test('rerunning adds demo studies missing from an older demo database', function () {
    $hospital = Hospital::factory()->create(['slug' => 'demo-heart-hospital']);
    User::factory()->for($hospital)->role(Role::Doctor)->create();

    $this->artisan('app:seed-heart-demo')->assertSuccessful();
    app(CurrentHospital::class)->set($hospital->id);
    AngioplastyStudy::query()->each(function (AngioplastyStudy $study) {
        $study->versions()->delete();
        $study->forceDelete();
    });

    $this->artisan('app:seed-heart-demo')->expectsOutputToContain('Added 2 demo studies')->assertSuccessful();

    expect(AngioplastyStudy::count())->toBe(2)
        ->and(Patient::count())->toBe(6)
        ->and(Admission::count())->toBe(6);
});
