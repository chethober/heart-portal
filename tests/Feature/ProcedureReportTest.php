<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Reports\ProcedureReport;
use App\Support\Procedures;
use Illuminate\Support\Facades\Storage;

dataset('narrative procedures', array_keys(Procedures::MODELS));

/**
 * Every field of the procedure filled in; choices take their $pass-th option, so a few passes
 * cover every option of every choice.
 *
 * @return array<string, mixed>
 */
function filledReport(string $procedure, int $pass): array
{
    $data = ['first_name' => 'Ali', 'last_name' => 'Rezaei', 'nationalID' => '0012345678', 'dob' => '1350/01/01', 'gender' => 'Male'];

    foreach (Procedures::fields($procedure) as $name => $field) {
        $data[$name] = match ($field['type']) {
            'choice' => (string) array_keys($field['options'])[$pass % count($field['options'])],
            'number' => 12.5 + $pass,
            'boolean' => $pass % 2 === 0,
            'date' => '1405/07/09',
            'time' => '08:30',
            default => "Note {$pass} for {$name}",
        };
    }

    return $data;
}

test('a narrative is built from every field and every option', function (string $procedure) {
    $passes = max([1, ...array_values(array_map(fn (array $field) => count($field['options'] ?? []), Procedures::fields($procedure)))]);

    for ($pass = 0; $pass < $passes; $pass++) {
        $data = filledReport($procedure, $pass);
        // A signed version's snapshot is JSON: the same data must read the same way.
        $snapshot = json_decode(json_encode($data), true);

        $live = ProcedureReport::for($procedure, $data);
        $signed = ProcedureReport::for($procedure, $snapshot);

        expect($live->sections())->not->toBeEmpty()
            ->and($live->titles())->not->toBeEmpty()
            ->and($live->signatureBoxes())->not->toBeEmpty()
            ->and($signed->sections())->toEqual($live->sections())
            ->and($signed->appendix())->toEqual($live->appendix());
    }
})->with('narrative procedures');

test('an empty study still builds a report', function (string $procedure) {
    $report = ProcedureReport::for($procedure, []);

    expect($report->sections())->toBeArray()
        ->and($report->appendix())->toBe([]);
})->with('narrative procedures');

test('every procedure has a narrative report', function () {
    expect(array_filter(array_keys(Procedures::MODELS), ProcedureReport::supports(...)))
        ->toBe(array_keys(Procedures::MODELS));
});

test('the RHC report tabulates pressures by site and gives the haemodynamics', function () {
    $report = ProcedureReport::for('rhc', [
        'Access' => 'femoral', 'catheter' => 'Swan-Ganz', 'mpa_o2' => 70, 'mpa_mean' => 35, 'mpa_systolic' => 50, 'mpa_diastolic' => 20,
        'pcwp_mean' => 15, 'ra_o2' => 0, 'cardiac_output' => 4, 'right_heart_result' => 'Pulmonary hypertension',
    ]);
    $sections = collect($report->sections());
    $text = $sections->flatMap(fn ($section) => array_map(fn ($line) => ($line['label'] ? $line['label'].': ' : '').$line['text'], $section['lines']))->implode("\n");

    expect($sections->first()['table'])->toBe([
        ['Site', 'O2 saturation %', 'Mean pressure (mmHg)', 'Systolic pressure (mmHg)', 'Diastolic pressure (mmHg)'],
        ['PCWP', '', '15', '', ''],
        ['MPA', '70', '35', '50', '20'],
    ])
        ->and($text)->toContain('Right heart catheterization was done via femoral vein and with Swan-Ganz catheter')
        ->and($text)->toContain('Pulmonary vascular resistance: 5 Wood units (calculated)')
        ->and($text)->toContain('Result: Pulmonary hypertension.')
        ->and($report->appendix())->toBe([]);

    // A recorded PVR is printed as recorded.
    expect(collect(ProcedureReport::for('rhc', ['pvr' => 3.2, 'mpa_mean' => 35, 'pcwp_mean' => 15, 'cardiac_output' => 4])->sections())
        ->flatMap(fn ($section) => array_column($section['lines'], 'text'))->all())->toContain('3.2 Wood units');
});

test('peripheral and intervention reports list technical details in a table', function (string $procedure, string $field) {
    $report = ProcedureReport::for($procedure, ['Access' => 'Radial', 'Indication_procedure' => 'Claudication', $field => 'Note']);

    expect($report->sections()[0]['table'][1])->toContain('Radial')
        ->and(collect($report->sections())->flatMap(fn ($section) => array_column($section['lines'], 'text'))->all())->toContain('Claudication')
        ->and($report->appendix())->toBe([]);
})->with([['peripheral', 'Recommendation'], ['intervention', 'Complications']]);

test('fields the narrative leaves out are listed, not dropped', function () {
    $report = ProcedureReport::for('angiography', ['lad_Vessel_patency' => '3', 'lad_proximal_part_segment' => '2', 'lad_prox_Stenotic' => '3']);

    expect(collect($report->sections())->flatMap(fn ($section) => array_column($section['lines'], 'text'))->implode(' '))
        ->toContain('Severe stenosis(70-89%) at proximal part segment');
});

test('the discharge summary prints right to left in Persian', function () {
    $hospital = Hospital::factory()->create();
    $nurse = User::factory()->for($hospital)->role(Role::Nurse)->create();
    $date = config('procedures.discharge.date_field');

    $this->actingAs($nurse)->post('/dashboard/procedures/discharge', ['nationalID' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei', $date => '1405/07/09'])->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();

    $this->actingAs($nurse)->get("/dashboard/procedures/discharge/{$study->id}")
        ->assertOk()
        ->assertSee('<div class="report-container" dir="rtl" lang="fa"', false)
        ->assertSee('پیش نویس — امضا نشده');
});

test('a signed procedure report shows the signature it was signed with, only to its hospital', function () {
    Storage::fake('local');
    $hospital = Hospital::factory()->create();
    $doctor = User::factory()->for($hospital)->role(Role::Doctor)->create(['name' => 'Dr. Signer']);
    $colleague = User::factory()->for($hospital)->role(Role::Doctor)->create();
    $stranger = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();

    $this->actingAs($doctor)->post('/dashboard/profile/signature', drawnSignature());
    $this->actingAs($doctor)->post('/dashboard/procedures/angiography', [
        'nationalID' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei',
        'DateOf_Date_of_angiography' => '1405/07/09', 'lad_Vessel_patency' => '1',
    ])->assertRedirect();
    $study = Procedures::model('angiography')::withoutGlobalScope(HospitalScope::class)->sole();
    $this->actingAs($doctor)->post("/dashboard/procedures/angiography/{$study->id}/finalize")->assertRedirect();
    $version = $study->versions()->withoutGlobalScope(HospitalScope::class)->sole();

    $url = route('users.signature', [$doctor, 'procedure_version' => $version->id]);
    $this->actingAs($doctor)->get("/dashboard/procedures/angiography/{$study->id}")
        ->assertOk()
        ->assertDontSee('DRAFT — NOT SIGNED')
        ->assertSee('Dr. Signer')
        ->assertSee($url, false);

    $this->actingAs($colleague)->get($url)->assertOk();
    $this->actingAs($stranger)->get($url)->assertNotFound();
    // The version must have been signed by the user in the URL.
    $this->actingAs($doctor)->get(route('users.signature', [$colleague, 'procedure_version' => $version->id]))->assertNotFound();
});
