<?php

use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\CurrentHospital;
use App\Support\HeartImport\HeartImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A small heart database (SQLite in memory) with heart's table and column names.
 */
beforeEach(function () {
    config(['database.connections.heart' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::purge('heart');
    $schema = Schema::connection('heart');

    $schema->create('patient', function (Blueprint $table) {
        $table->increments('patientId');
        $table->string('nationalcode')->nullable();
        $table->string('patientName')->nullable();
        $table->string('patientFamily')->nullable();
        $table->string('DateOf_dateOfBirth')->nullable();
        $table->string('gender')->nullable();
        $table->decimal('patientMobile', 12, 2)->nullable();
    });
    $schema->create('admit', function (Blueprint $table) {
        $table->increments('admitId');
        $table->integer('patientId');
        $table->integer('centerId')->nullable();
        $table->string('ward')->nullable();
        $table->string('physician')->nullable();
        $table->string('DateOf_admitdate')->nullable();
        $table->string('DateOf_dischargedate')->nullable();
        $table->string('AdmCode')->nullable();
    });
    $schema->create('ward', function (Blueprint $table) {
        $table->increments('wardId');
        $table->string('Title');
    });
    $schema->create('angiography', function (Blueprint $table) {
        $table->increments('idAngio');
        $table->integer('patientId');
        $table->integer('admitId')->nullable();
        $table->string('DateOf_Date_of_angiography')->nullable();
        $table->string('status')->nullable();
        $table->string('lad_Vessel_patency')->nullable();
        $table->text('lad_pci_Description')->nullable();
        $table->string('auto_createtime')->nullable();
    });
    $schema->create('echo', function (Blueprint $table) {
        $table->increments('idEcho');
        $table->integer('patientId');
        $table->string('DateOf_date_echo')->nullable();
        $table->string('Echo_type')->nullable();
        $table->string('la_smoky')->nullable();
        $table->string('pa_MPA')->nullable();
        $table->string('EF')->nullable();
    });
    // Heart's users; never read.
    $schema->create('tbusers', function (Blueprint $table) {
        $table->increments('id');
        $table->string('username');
        $table->string('password');
    });

    $heart = DB::connection('heart');
    $heart->table('patient')->insert([
        ['nationalcode' => '۰۰۱۲۳۴۵۶۷۸', 'patientName' => 'Ali', 'patientFamily' => 'Rezaei', 'DateOf_dateOfBirth' => '13500101', 'gender' => '1', 'patientMobile' => 9121234567],
        ['nationalcode' => '0098765432', 'patientName' => 'Sara', 'patientFamily' => 'Ahmadi', 'DateOf_dateOfBirth' => '13600505', 'gender' => '2', 'patientMobile' => null],
        // A duplicate of the first patient, and a patient without a national code.
        ['nationalcode' => '0012345678', 'patientName' => 'Ali', 'patientFamily' => 'Rezaei', 'DateOf_dateOfBirth' => null, 'gender' => '1', 'patientMobile' => null],
        ['nationalcode' => null, 'patientName' => 'Nameless', 'patientFamily' => null, 'DateOf_dateOfBirth' => null, 'gender' => null, 'patientMobile' => null],
    ]);
    $heart->table('ward')->insert(['Title' => 'CCU']);
    $heart->table('admit')->insert([
        ['patientId' => 1, 'centerId' => 1, 'ward' => '1', 'physician' => null, 'DateOf_admitdate' => '14050701', 'DateOf_dischargedate' => '14050705', 'AdmCode' => '5001'],
        ['patientId' => 2, 'centerId' => 1, 'ward' => '1', 'physician' => null, 'DateOf_admitdate' => '14050702', 'DateOf_dischargedate' => null, 'AdmCode' => null],
    ]);
    $heart->table('angiography')->insert([
        ['patientId' => 1, 'admitId' => 1, 'DateOf_Date_of_angiography' => '14050702', 'status' => 'Elective_angiography', 'lad_Vessel_patency' => '1', 'lad_pci_Description' => 'Normal LAD.', 'auto_createtime' => '2026-09-23 10:00:00'],
        // "9" is heart's Missing placeholder for the patency and "Missing" for the status.
        ['patientId' => 2, 'admitId' => null, 'DateOf_Date_of_angiography' => 'not a date', 'status' => 'Missing', 'lad_Vessel_patency' => '9', 'lad_pci_Description' => null, 'auto_createtime' => null],
    ]);
    $heart->table('echo')->insert([
        ['patientId' => 1, 'DateOf_date_echo' => '14050703', 'Echo_type' => '3', 'la_smoky' => '3', 'pa_MPA' => '2.5', 'EF' => '55'],
    ]);
    $heart->table('tbusers')->insert(['username' => 'admin', 'password' => 'secret']);

    $this->hospital = Hospital::factory()->create(['slug' => 'mehr']);
});

/**
 * Reads the hospital's records without a signed-in user.
 */
function asHospital(Hospital $hospital): void
{
    app(CurrentHospital::class)->set($hospital->id);
}

test('heart patients, admissions and studies are imported into the chosen hospital', function () {
    $users = User::count();

    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();

    asHospital($this->hospital);
    $ali = Patient::whereNationalId('0012345678')->sole();
    expect(Patient::count())->toBe(2)
        ->and($ali->first_name)->toBe('Ali')
        ->and($ali->dob)->toBe('1350/01/01')
        ->and($ali->gender)->toBe('Male')
        ->and($ali->phone_number)->toBe('09121234567')
        ->and(User::count())->toBe($users);

    $admission = Admission::where('legacy_id', 1)->sole();
    expect($admission->patient_id)->toBe($ali->id)
        ->and($admission->ward)->toBe('CCU')
        // Linked to the hospital's ward of that name, created when missing.
        ->and($admission->wardRecord?->name)->toBe('CCU')
        ->and($admission->wardRecord?->hospital_id)->toBe($this->hospital->id)
        ->and($admission->admitted_on)->toBe('1405/07/01')
        ->and($admission->discharged_on)->toBe('1405/07/05')
        ->and($admission->his_id)->toBe('5001');

    $angio = AngiographyStudy::where('legacy_id', 1)->sole();
    expect($angio->status)->toBe(StudyStatus::Final)
        ->and($angio->hospital_id)->toBe($this->hospital->id)
        ->and($angio->admission_id)->toBe($admission->id)
        ->and($angio->angiography_status)->toBe('Elective_angiography')
        ->and($angio->lad_Vessel_patency)->toBe('1')
        ->and($angio->performedOn())->toBe('1405/07/02')
        ->and($angio->versions()->sole()->reason)->toBe(HeartImporter::IMPORTED);

    $blank = AngiographyStudy::where('legacy_id', 2)->sole();
    expect($blank->angiography_status)->toBeNull()
        ->and($blank->lad_Vessel_patency)->toBeNull()
        ->and($blank->DateOf_Date_of_angiography)->toBeNull();

    // Heart's echo table becomes an echo study, with codes turned into the new form's text.
    $echo = EchoStudy::where('legacy_id', 1)->sole();
    expect($echo->echo_date)->toBe('1405/07/03')
        ->and($echo->Echo_type)->toBe('Other')
        ->and($echo->other_Echo_type)->toBe('Tissue Doppler imaging (TDI)')
        ->and($echo->la_smoky)->toBe('1')
        ->and($echo->la_other_des)->toBe('LA smoke: moderate smoky pattern')
        ->and($echo->pv_other_des)->toBe('MPA size (cm): 2.5');
});

test('importing again changes nothing, and picks up what changed in heart', function () {
    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();
    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();

    asHospital($this->hospital);
    expect(Patient::count())->toBe(2)
        ->and(Admission::count())->toBe(2)
        ->and(AngiographyStudy::count())->toBe(2)
        ->and(AngiographyStudy::where('legacy_id', 1)->sole()->versions()->count())->toBe(1);

    DB::connection('heart')->table('angiography')->where('idAngio', 1)->update(['lad_Vessel_patency' => '2']);
    $this->artisan('heart:import', ['--hospital' => 'mehr', '--only' => ['angiography']])->assertSuccessful();

    $study = AngiographyStudy::where('legacy_id', 1)->sole();
    expect($study->lad_Vessel_patency)->toBe('2')
        ->and($study->versions()->count())->toBe(2)
        ->and($study->versions->last()->reason)->toBe(HeartImporter::REIMPORTED);
});

test('studies amended or deleted in Heart Portal are not overwritten', function () {
    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();
    asHospital($this->hospital);
    AngiographyStudy::where('legacy_id', 1)->sole()->forceFill(['status' => StudyStatus::Amended, 'lad_pci_Description' => 'Corrected.'])->save();
    AngiographyStudy::where('legacy_id', 2)->sole()->delete();

    DB::connection('heart')->table('angiography')->update(['lad_pci_Description' => 'Changed in heart.']);
    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();

    expect(AngiographyStudy::where('legacy_id', 1)->sole()->lad_pci_Description)->toBe('Corrected.')
        ->and(AngiographyStudy::count())->toBe(1);
});

test('a dry run saves nothing', function () {
    $this->artisan('heart:import', ['--hospital' => 'mehr', '--dry-run' => true])
        ->expectsOutputToContain('nothing was saved')
        ->assertSuccessful();

    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(0)
        ->and(AngiographyStudy::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('another hospital\'s patient with the same national ID is left alone', function () {
    $other = Hospital::factory()->create();
    asHospital($other);
    $theirs = Patient::create(['national_id' => '0012345678', 'first_name' => 'Other', 'last_name' => 'Hospital']);

    $this->artisan('heart:import', ['--hospital' => 'mehr'])->assertSuccessful();

    expect($theirs->fresh()->first_name)->toBe('Other')
        ->and(Patient::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $this->hospital->id)->count())->toBe(2)
        ->and(AngiographyStudy::withoutGlobalScope(HospitalScope::class)->where('hospital_id', $other->id)->count())->toBe(0);
});

test('a heart database with several centers needs one chosen', function () {
    DB::connection('heart')->table('admit')->where('admitId', 2)->update(['centerId' => 2]);

    $this->artisan('heart:import', ['--hospital' => 'mehr'])
        ->expectsOutputToContain('several centers')
        ->assertFailed();
    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);

    $this->artisan('heart:import', ['--hospital' => 'mehr', '--center' => '1'])->assertSuccessful();

    asHospital($this->hospital);
    expect(Patient::pluck('first_name')->all())->toBe(['Ali'])
        ->and(Admission::pluck('legacy_id')->all())->toBe([1])
        ->and(AngiographyStudy::pluck('legacy_id')->all())->toBe([1]);
});

test('the hospital and table names are checked', function () {
    $this->artisan('heart:import')->assertFailed();
    $this->artisan('heart:import', ['--hospital' => 'nowhere'])->assertFailed();
    $this->artisan('heart:import', ['--hospital' => 'mehr', '--only' => ['tbusers']])->assertFailed();
    $this->artisan('heart:import', ['--hospital' => (string) $this->hospital->id, '--only' => ['patients,admissions']])->assertSuccessful();

    asHospital($this->hospital);
    expect(Admission::count())->toBe(2)
        ->and(AngiographyStudy::count())->toBe(0);
});
