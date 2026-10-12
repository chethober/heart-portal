<?php

use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Support\CurrentHospital;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The HIS registry view as a SQLite table, with the view's column names (spaces included).
 */
beforeEach(function () {
    config([
        'database.connections.his' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'heart.his.view' => 'registry_adm',
    ]);
    DB::purge('his');

    Schema::connection('his')->create('registry_adm', function (Blueprint $table) {
        $table->string('Adm Code')->nullable();
        $table->string('NationalCode')->nullable();
        $table->string('Name')->nullable();
        $table->string('Family')->nullable();
        $table->string('Sex')->nullable();
        $table->string('Birth Date')->nullable();
        $table->string('MobileNo')->nullable();
        $table->string('Tel')->nullable();
        $table->string('Discharge_ward')->nullable();
        $table->string('physician')->nullable();
        $table->string('Date')->nullable();
        $table->string('DisDate')->nullable();
    });

    DB::connection('his')->table('registry_adm')->insert([
        ['Adm Code' => '9001', 'NationalCode' => '0012345678', 'Name' => 'Ali', 'Family' => 'Rezaei', 'Sex' => 'مرد', 'Birth Date' => '1350/01/01', 'MobileNo' => '09121234567', 'Tel' => null, 'Discharge_ward' => 'CCU', 'physician' => 'Dr. Karimi', 'Date' => '1405/07/01 10:30', 'DisDate' => null],
        ['Adm Code' => '9002', 'NationalCode' => '0098765432', 'Name' => 'Sara', 'Family' => 'Ahmadi', 'Sex' => 'زن', 'Birth Date' => null, 'MobileNo' => null, 'Tel' => null, 'Discharge_ward' => 'Post CCU', 'physician' => null, 'Date' => '1404/12/20 08:00', 'DisDate' => '1404/12/25 12:00'],
        // No national code: reported, not imported.
        ['Adm Code' => '9003', 'NationalCode' => null, 'Name' => 'X', 'Family' => 'Y', 'Sex' => null, 'Birth Date' => null, 'MobileNo' => null, 'Tel' => null, 'Discharge_ward' => null, 'physician' => null, 'Date' => '1405/07/02 09:00', 'DisDate' => null],
    ]);

    $this->hospital = Hospital::factory()->create(['slug' => 'mehr']);
});

test('HIS admissions and their patients are imported, and updated on the next run', function () {
    $this->artisan('his:import-admissions', ['--hospital' => 'mehr'])
        ->expectsOutputToContain('no national code')
        ->assertSuccessful();

    app(CurrentHospital::class)->set($this->hospital->id);
    $admission = Admission::where('his_id', '9001')->sole();
    expect(Patient::count())->toBe(2)
        ->and(Admission::count())->toBe(2)
        ->and($admission->patient->gender)->toBe('Male')
        ->and($admission->ward)->toBe('CCU')
        ->and($admission->admitted_on)->toBe('1405/07/01')
        ->and($admission->discharged_on)->toBeNull();

    // Staff corrected the name; the HIS later records the discharge.
    $admission->patient->update(['first_name' => 'Aly']);
    DB::connection('his')->table('registry_adm')->where('Adm Code', '9001')->update(['DisDate' => '1405/07/04 11:00']);

    $this->artisan('his:import-admissions', ['--hospital' => 'mehr'])->assertSuccessful();

    expect(Admission::count())->toBe(2)
        ->and($admission->fresh()->discharged_on)->toBe('1405/07/04')
        ->and($admission->patient->fresh()->first_name)->toBe('Aly');
});

test('--since and --limit narrow what is read', function () {
    $this->artisan('his:import-admissions', ['--hospital' => 'mehr', '--since' => '1405/01/01'])->assertSuccessful();

    app(CurrentHospital::class)->set($this->hospital->id);
    expect(Admission::pluck('his_id')->all())->toBe(['9001']);

    $this->artisan('his:import-admissions', ['--hospital' => 'mehr', '--since' => 'yesterday'])->assertFailed();
});

test('a dry run saves nothing, and the hospital is required', function () {
    $this->artisan('his:import-admissions')->assertFailed();
    $this->artisan('his:import-admissions', ['--hospital' => 'mehr', '--dry-run' => true])->assertSuccessful();

    expect(Admission::withoutGlobalScope(HospitalScope::class)->count())->toBe(0)
        ->and(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('admissions only land in the chosen hospital', function () {
    $other = Hospital::factory()->create();
    app(CurrentHospital::class)->set($other->id);
    $theirs = Patient::create(['national_id' => '0012345678', 'first_name' => 'Theirs', 'last_name' => 'Patient']);

    $this->artisan('his:import-admissions', ['--hospital' => 'mehr'])->assertSuccessful();

    expect($theirs->fresh()->first_name)->toBe('Theirs')
        ->and(Admission::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $other->id)->count())->toBe(0)
        ->and(Admission::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $this->hospital->id)->count())->toBe(2);
});
