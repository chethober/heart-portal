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
            ->and($live->title())->not->toBe('')
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

test('the RHC report states the indication, or the written-in one for other', function () {
    $lines = fn (array $data) => collect(ProcedureReport::for('rhc', $data)->sections())
        ->flatMap(fn ($section) => array_map(fn ($line) => ($line['label'] ? $line['label'].': ' : '').$line['text'], $section['lines']))->implode("\n");

    expect($lines(['indication' => '3']))->toContain('Indication: Evaluation before valve surgery')
        ->and($lines(['indication' => '99', 'indication_other' => 'Congenital shunt work-up']))->toContain('Indication: Congenital shunt work-up')
        ->and($lines(['Access' => 'femoral']))->not->toContain('Indication');
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

test('the discharge summary prints right to left in Persian for a Persian reader', function () {
    $hospital = Hospital::factory()->create();
    $nurse = User::factory()->for($hospital)->role(Role::Nurse)->create();
    $date = config('procedures.discharge.date_field');

    $this->actingAs($nurse)->post('/dashboard/procedures/discharge', ['nationalID' => '0012345678', 'first_name' => 'Ali', 'last_name' => 'Rezaei', $date => '1405/07/09', 'history_mode' => 'auto', 'clinicalcomplaint_syncope' => '1'])->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();

    $this->actingAs($nurse)->withCookie('locale', 'fa')->get("/dashboard/procedures/discharge/{$study->id}")
        ->assertOk()
        ->assertSee('<div class="report-container" dir="rtl" lang="fa"', false)
        ->assertSee('پیش نویس — امضا نشده')
        ->assertSee('برگ خلاصه پرونده')
        ->assertSee('شکایت اصلی بیمار و تشخیص اولیه')
        ->assertSee('مهر و امضای کارورز');

    // In English the template and headings are English; heart's Persian sentences are kept.
    $this->actingAs($nurse)->withCookie('locale', 'en')->get("/dashboard/procedures/discharge/{$study->id}")
        ->assertOk()
        ->assertSee('<div class="report-container" dir="ltr" lang="en"', false)
        ->assertSee('DRAFT — NOT SIGNED')
        ->assertSee('Chief complaint and initial diagnosis')
        ->assertSee('Stamp and signature of the intern')
        ->assertSee('به دلیل سنکوب')
        ->assertDontSee('شکایت اصلی بیمار و تشخیص اولیه');
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

test('the summary the discharge summary extracts is each procedure\'s result', function () {
    // Angiography: the result and the recommendation, in the discharge summary's wording.
    expect(ProcedureReport::for('angiography', ['lad_Vessel_patency' => '3', 'lad_proximal_part_segment' => '2', 'lad_prox_Stenotic' => '3', 'result_legacy' => '6', 'recommend_cabg' => '1'])->summaryText())
        ->toBe('آنژیوگرافی عروق کرونر انجام شد Three-vessel CAD (3VD) • Recommendation: CABG')
        ->and(ProcedureReport::for('angiography', ['recommend_cabg' => '1'])->summaryText())->toBe('آنژیوگرافی عروق کرونر انجام شد • Recommendation: CABG')
        ->and(ProcedureReport::for('angiography', ['lad_Vessel_patency' => '3'])->summaryText())->toBe('');

    // Angioplasty: the result of PCI only.
    expect(ProcedureReport::for('angioplasty', ['t1_territory_of_intervention' => '2', 't1_Result_Of_PCI_1' => '1', 'recommendation' => 'Follow up'])->summaryText())
        ->toBe('successful PCI on LAD');

    // Right heart catheterization: the result only.
    expect(ProcedureReport::for('rhc', ['right_heart_result' => 'Severe pulmonary hypertension', 'right_heart_recommendation' => 'Refer', 'cardiac_output' => 4])->summaryText())
        ->toBe('Severe pulmonary hypertension.');

    // Peripheral angiography and cardiac intervention: the final result only.
    foreach (['peripheral', 'intervention'] as $procedure) {
        expect(ProcedureReport::for($procedure, ['Description_procedure' => 'Stenting', 'Final_result' => 'Successful'])->summaryText())->toBe('Successful');
    }
});
