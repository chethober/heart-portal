<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create(['slug' => 'mehr']);
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->ali = Patient::factory()->for($this->hospital)->create(['national_id' => '0012345679', 'first_name' => 'علي', 'last_name' => 'كريمي']);
    $this->sara = Patient::factory()->for($this->hospital)->create(['national_id' => '0098765432', 'first_name' => 'Sara', 'last_name' => 'Karimi']);
});

function hisRegistry(array $rows): void
{
    config([
        'database.connections.his' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        'heart.his.view' => 'registry_adm',
        'heart.his.lookup_hospital' => 'mehr',
    ]);
    DB::purge('his');
    Schema::connection('his')->create('registry_adm', function (Blueprint $table) {
        foreach (['Adm Code', 'NationalCode', 'Name', 'Family', 'Sex', 'Birth Date', 'MobileNo', 'Tel', 'Discharge_ward', 'physician', 'Date', 'DisDate'] as $column) {
            $table->string($column)->nullable();
        }
    });
    DB::connection('his')->table('registry_adm')->insert($rows);
}

test('a national ID opens the patient, with or without a leading zero or Persian digits', function (string $query) {
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID='.urlencode($query))
        ->assertOk()
        ->assertSee(route('patients.show', $this->ali), false);
})->with(['0012345679', '00012345679', '۰۰۱۲۳۴۵۶۷۹']);

test('names are found whether written with Arabic or Persian ی and ک, with or without spaces', function (string $query) {
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID='.urlencode($query))
        ->assertOk()
        ->assertSee(route('patients.show', $this->ali), false);
})->with(['علی', 'کریمی', 'علیکریمی', 'علي كريمي']);

test('several matches are listed', function () {
    // A single match opens the patient.
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=karimi')
        ->assertOk()
        ->assertSee(route('patients.show', $this->sara), false);

    Patient::factory()->for($this->hospital)->create(['first_name' => 'Reza', 'last_name' => 'Karimian']);
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=karimi')
        ->assertOk()
        ->assertSee('2 patients found')
        ->assertSee('Reza');
});

test('an HIS admission number finds its patient, also as heart stored it', function () {
    Admission::factory()->for($this->sara)->create(['his_id' => '4567.00']);

    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=4567')
        ->assertOk()
        ->assertSee(route('patients.show', $this->sara), false);
});

test('the search never finds another hospital\'s patients, and the search text is not logged', function () {
    $outsider = User::factory()->for(Hospital::factory())->role(Role::Nurse)->create();

    $this->actingAs($outsider)->get('/dashboard/patients?nationalID=Karimi')->assertOk()->assertSee('No patients found.')->assertDontSee('Sara');
    $this->actingAs($outsider)->get('/dashboard/patients?nationalID=0098765432')->assertOk()->assertDontSee('Sara');

    $logged = AuditLog::withoutGlobalScope(HospitalScope::class)->get()->toJson(JSON_UNESCAPED_UNICODE);
    expect($logged)->not->toContain('Karimi')->not->toContain('0098765432');
});

test('a patient not on record is looked up in the HIS and imported', function () {
    hisRegistry([['Adm Code' => '9001', 'NationalCode' => '1234567891', 'Name' => 'Nima', 'Family' => 'Sadeghi', 'Sex' => 'مرد', 'Birth Date' => null, 'MobileNo' => null, 'Tel' => null, 'Discharge_ward' => 'CCU', 'physician' => null, 'Date' => '1405/07/01 10:30', 'DisDate' => null]]);

    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=1234567891')
        ->assertOk()
        ->assertSee('found in the hospital information system and imported');

    $patient = Patient::withoutGlobalScope(HospitalScope::class)->where('last_name', 'Sadeghi')->sole();
    expect($patient->hospital_id)->toBe($this->hospital->id)
        ->and(Admission::withoutGlobalScope(HospitalScope::class)->where('his_id', '9001')->sole()->patient_id)->toBe($patient->id);

    // By admission number too.
    hisRegistry([['Adm Code' => '9100', 'NationalCode' => '2234567891', 'Name' => 'Mina', 'Family' => 'Jafari', 'Sex' => 'زن', 'Birth Date' => null, 'MobileNo' => null, 'Tel' => null, 'Discharge_ward' => null, 'physician' => null, 'Date' => '1405/07/02 10:30', 'DisDate' => null]]);
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=9100')->assertOk()->assertSee('found in the hospital information system and imported');
    expect(Patient::withoutGlobalScope(HospitalScope::class)->where('last_name', 'Jafari')->sole()->hospital_id)->toBe($this->hospital->id);
});

test('the HIS is only asked for its own hospital, and a missing one does not break the search', function () {
    hisRegistry([['Adm Code' => '9001', 'NationalCode' => '1234567891', 'Name' => 'Nima', 'Family' => 'Sadeghi', 'Sex' => null, 'Birth Date' => null, 'MobileNo' => null, 'Tel' => null, 'Discharge_ward' => null, 'physician' => null, 'Date' => '1405/07/01', 'DisDate' => null]]);
    $other = User::factory()->for(Hospital::factory())->role(Role::Nurse)->create();

    $this->actingAs($other)->get('/dashboard/patients?nationalID=1234567891')->assertOk()->assertDontSee('Sadeghi');
    expect(Patient::withoutGlobalScope(HospitalScope::class)->where('last_name', 'Sadeghi')->exists())->toBeFalse();

    config(['database.connections.his' => ['driver' => 'sqlite', 'database' => '/nonexistent/his.sqlite', 'prefix' => '']]);
    DB::purge('his');
    $this->actingAs($this->nurse)->get('/dashboard/patients?nationalID=1234567891')->assertOk()->assertSee('No echo reports found');
});
