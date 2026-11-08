<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\AngiographyStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Reports\ProcedureReport;
use App\Support\Procedures;
use Illuminate\Support\Js;

dataset('procedures', array_keys(Procedures::MODELS));

/**
 * A minimal valid form: the patient, the procedure date and one unconditional finding.
 *
 * @return array{0: array<string, string>, 1: string} The payload and how the report shows the finding.
 */
function procedurePayload(string $procedure): array
{
    $fields = collect(Procedures::fields($procedure))->reject(fn (array $field) => isset($field['show_if']));
    // Forms without choices (RHC, peripheral, intervention) are filled with a note instead.
    $name = $fields->search(fn (array $field) => $field['type'] === 'choice')
        ?: $fields->search(fn (array $field) => in_array($field['type'], ['text', 'multiline'], true));
    $field = $fields[$name];
    $value = $field['type'] === 'choice' ? (string) array_key_first($field['options']) : 'Demo finding';

    // A Persian report (the discharge summary) prints a choice's Persian wording.

    return [[
        'nationalID' => '0012345678',
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        config("procedures.{$procedure}.date_field") => '1405/07/09',
        $name => $value,
    ], $field['type'] === 'choice' ? ($field['fa_options'] ?? $field['options'])[$value] : $value];
}

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
});

test('each heart form records, reports and signs a study', function (string $procedure) {
    [$payload, $label] = procedurePayload($procedure);

    $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, $procedure))
        ->assertOk()
        ->assertSee('dir="'.Procedures::dir($procedure).'"', false);
    $this->actingAs($this->nurse)->post("/dashboard/procedures/{$procedure}", $payload)->assertRedirect();

    $study = Procedures::model($procedure)::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->hospital_id)->toBe($this->hospital->id)
        ->and($study->performedOn())->toBe('1405/07/09');

    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/{$study->id}")
        ->assertOk()
        ->assertSee($label)
        ->assertSee('DRAFT — NOT SIGNED');

    $this->actingAs($this->nurse)->post("/dashboard/procedures/{$procedure}/{$study->id}/finalize")->assertForbidden();
    // The discharge summary is signed in steps (intern, resident, attending physician).
    foreach (range(0, count(Procedures::model($procedure)::SIGNOFFS)) as $signature) {
        $this->actingAs($this->doctor)->post("/dashboard/procedures/{$procedure}/{$study->id}/finalize")->assertRedirect();
    }

    expect($study->fresh()->status)->toBe(StudyStatus::Final)
        ->and($study->versions()->sole()->study_type)->toBe($procedure);
})->with('procedures');

test('each heart form is hidden from other hospitals', function (string $procedure) {
    [$payload] = procedurePayload($procedure);
    $this->actingAs($this->nurse)->post("/dashboard/procedures/{$procedure}", $payload)->assertRedirect();
    $study = Procedures::model($procedure)::withoutGlobalScope(HospitalScope::class)->sole();

    $outsider = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();

    $this->actingAs($outsider)->get("/dashboard/procedures/{$procedure}/{$study->id}")->assertNotFound();
    $this->actingAs($outsider)->put("/dashboard/procedures/{$procedure}/{$study->id}", $payload)->assertNotFound();
    $this->actingAs($outsider)->post("/dashboard/procedures/{$procedure}/{$study->id}/finalize")->assertNotFound();
    expect(Procedures::model($procedure)::count())->toBe(0);
})->with('procedures');

test('the shared patient step lists echo and every heart form', function () {
    $response = $this->actingAs($this->nurse)->get('/dashboard')->assertOk()->assertSee('value="echo"', false);

    foreach (array_keys(Procedures::MODELS) as $procedure) {
        $response->assertSee('value="'.$procedure.'"', false)->assertSee(Procedures::label($procedure));
    }
});

test('staff who cannot record studies get no New Study menu', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get('/dashboard/patients')->assertOk()->assertDontSee(route('procedures.create', 'angiography'));
});

test('each heart form previews its report live without saving', function (string $procedure) {
    [$payload, $label] = procedurePayload($procedure);

    $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, $procedure))
        ->assertOk()
        ->assertSee(str_replace('/', '\\/', route('procedures.preview', $procedure)), false)
        ->assertSee('Live Report Preview')
        ->assertSee('class="section-nav-btn"', false);

    $this->actingAs($this->nurse)->post("/dashboard/procedures/{$procedure}/preview", $payload)
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee($label)
        ->assertSee('DRAFT');

    expect(Procedures::model($procedure)::count())->toBe(0);
})->with('procedures');

test('the preview needs the form permission and never shows another hospital\'s admission', function () {
    [$payload] = procedurePayload('angiography');
    $foreign = Admission::factory()->for(Patient::factory()->for(Hospital::factory()))->create(['ward' => 'Elsewhere ward']);

    $this->actingAs($this->nurse)->post('/dashboard/procedures/angiography/preview', [...$payload, 'admission_id' => $foreign->id])
        ->assertOk()
        ->assertDontSee('Elsewhere ward');

    $this->nurse->revokePermissionTo(['angiography.create', 'angiography.edit']);
    $this->actingAs($this->nurse)->post('/dashboard/procedures/angiography/preview', $payload)->assertForbidden();
});

test('saving a discharge summary keeps its retired complication fields and its written history', function () {
    [$payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', $payload)->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();
    // As heart's import leaves them: structured complications and a history without a mode.
    $study->forceFill(['diseasestatus' => '1', 'comp_after_cva' => '1', 'non_specific_explanation' => 'Imported history'])->save();

    $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$study->id}/edit")
        ->assertOk()
        ->assertDontSee('name="comp_after_cva"', false)
        ->assertSee('Imported history');
    expect($study->fresh()->history_mode)->toBe('manual');

    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", [
        ...$payload, 'diseasestatus' => '1', 'diseasestatus_other' => 'Hematoma',
        'history_mode' => 'manual', 'non_specific_explanation' => 'Imported history',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $study->refresh();
    expect($study->comp_after_cva)->toBe('1')
        ->and($study->diseasestatus_other)->toBe('Hematoma')
        ->and($study->non_specific_explanation)->toBe('Imported history');

    // Auto builds the history from the complaints, so the written one is dropped.
    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", [...$payload, 'history_mode' => 'auto', 'non_specific_explanation' => 'Imported history'])
        ->assertRedirect();
    expect($study->fresh()->non_specific_explanation)->toBeNull();
});

test('the history\'s complaint boxes are only shown and kept in Auto mode, which a new summary starts in', function () {
    $html = $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()->getContent();
    expect($html)->toMatch('/<option value="auto" selected/')
        ->toContain('x-show="v[&#039;history_mode&#039;] === &#039;auto&#039;"');

    [$payload] = procedurePayload('discharge');
    $complaints = ['clinicalcomplaint_syncope' => '1', 'symptoms_others' => 'Dizziness'];
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$payload, ...$complaints, 'history_mode' => 'manual', 'non_specific_explanation' => 'Written by hand'])
        ->assertSessionHasNoErrors()->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->clinicalcomplaint_syncope)->toBeNull()
        ->and($study->symptoms_others)->toBeNull()
        ->and($study->non_specific_explanation)->toBe('Written by hand');

    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", [...$payload, ...$complaints, 'history_mode' => 'auto'])->assertRedirect();
    expect($study->fresh()->clinicalcomplaint_syncope)->toBe('1');
});

test('the history\'s complaints are one checkbox each, and dyspnea class shows only with dyspnea on exertion in Auto mode', function () {
    $html = $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()->getContent();
    expect($html)->toContain('<input type="checkbox" name="clinicalcomplaint_syncope" value="1"')
        ->not->toContain('<select id="f-clinicalcomplaint_syncope"')
        ->toContain('x-on:change="v[\'clinicalcomplaint_asthmainactivity\'] = $event.target.checked ? \'1\' : \'\'"')
        ->toContain('x-show="v[&#039;history_mode&#039;] === &#039;auto&#039; &amp;&amp; v[&#039;clinicalcomplaint_asthmainactivity&#039;] === &#039;1&#039;"');
});

test('the discharge diagnoses are checkboxes, ticked only for Yes, and the arrhythmia type shows with arrhythmia', function () {
    [$payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$payload, 'stableangina' => '1', 'arrhythmia' => '1', 'typeOfArrhythmia' => 'AF', 'for_CABG' => '1'])
        ->assertSessionHasNoErrors()->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->stableangina)->toBe('1')->and($study->typeOfArrhythmia)->toBe('AF')->and($study->for_CABG)->toBe('1');

    // Heart's No (2) and Unknown (9) are not ticked.
    $study->forceFill(['heart_failure' => '2', 'lung_emboli' => '9'])->save();
    $html = $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$study->id}/edit")->assertOk()->getContent();
    expect($html)->toContain('<input type="checkbox" name="stableangina" value="1" checked')
        ->toContain('<input type="checkbox" name="for_CABG" value="1" checked')
        ->toMatch('/<input type="checkbox" name="heart_failure" value="1"\s*>/')
        ->toMatch('/<input type="checkbox" name="lung_emboli" value="1"\s*>/')
        ->not->toContain('<select id="f-stableangina"')
        ->toContain('x-show="v[&#039;arrhythmia&#039;] === &#039;1&#039;"');

    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", [...$payload, 'stableangina' => '1'])->assertRedirect();
    expect($study->fresh()->stableangina)->toBe('1')->and($study->fresh()->arrhythmia)->toBeNull()->and($study->fresh()->typeOfArrhythmia)->toBeNull();
});

test('the discharge risk factors (but smoking), surgical history and signs are checkboxes', function () {
    [$payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$payload, 'rf_diabetes_hx' => '1', 'dm_tx_oral' => '1', 'rf_opium' => '1',
        'cad_family_hx' => '1', 'cad_family_hx_father' => '1', 'rf_smoking' => '2', 'HxPCI' => '1', 'signs_rales' => '1'])
        ->assertSessionHasNoErrors()->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->only('rf_diabetes_hx', 'dm_tx_oral', 'rf_opium', 'cad_family_hx', 'rf_smoking', 'HxPCI', 'signs_rales'))
        ->toBe(['rf_diabetes_hx' => '1', 'dm_tx_oral' => '1', 'rf_opium' => '1', 'cad_family_hx' => '1', 'rf_smoking' => '2', 'HxPCI' => '1', 'signs_rales' => '1'])
        ->and($study->cad_family_hx_father)->toBeTruthy();

    // Heart's Unknown (2) and No (3) are not ticked.
    $study->forceFill(['hxPrevMI_main' => '2', 'rf_hyperchol_hx' => '3'])->save();
    $html = $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$study->id}/edit")->assertOk()->getContent();
    foreach (['rf_diabetes_hx', 'dm_tx_oral', 'rf_opium', 'cad_family_hx', 'HxPCI', 'signs_rales'] as $name) {
        expect($html)->toContain('<input type="checkbox" name="'.$name.'" value="1" checked');
    }
    foreach (['hxPrevMI_main', 'rf_hyperchol_hx', 'rf_hookah', 'crt', 'signs_s3'] as $name) {
        expect($html)->toMatch('/<input type="checkbox" name="'.$name.'" value="1"\s*(x-on:change="[^"]*")?\s*>/')
            ->not->toContain('<select id="f-'.$name.'"');
    }
    expect($html)->toContain('<select id="f-rf_smoking"');

    // The risk factors stay one run of checkboxes; what a ticked one reveals follows it, captioned.
    expect(strpos($html, 'name="rf_hyperchol_hx"'))->toBeLessThan(strpos($html, 'name="dm_tx_regimen"'))
        ->and($html)->toMatch('/x-show="v\[&#039;rf_diabetes_hx&#039;\] === &#039;1&#039;" x-cloak\s*>\s*<label>Diabetes<\/label>/')
        ->toMatch('/<label>Family history of heart disease<\/label>\s*<div class="cx-group" role="group">\s*<label class="cx-chip"\s*>\s*<input type="checkbox" name="cad_family_hx_father"/')
        ->toContain('>Hypertension treatment</label>');

    // Unticking clears what it revealed.
    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", [...$payload, 'dm_tx_oral' => '1'])->assertRedirect();
    expect($study->fresh()->rf_diabetes_hx)->toBeNull()->and($study->fresh()->dm_tx_oral)->toBeNull();
});

test('discharge medications can be added from the medications list', function () {
    $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()
        ->assertSee('x-data="drugInsert('.Js::from(route('drugs.search')), false)
        ->assertSee('placeholder="Add from the medications list…"', false)
        ->assertSee('<textarea id="f-drugDischarge" name="drugDischarge"', false);
});

test('the discharge procedures have an Extract button for the procedure types the user may view', function () {
    $response = $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk();

    $response->assertSee('x-data="procedureExtract('.Js::from(['angiography' => route('procedures.latest-summary', 'angiography')]).', '.Js::from('f-angiography_coronersFor').')"', false)
        ->assertSee('x-data="procedureExtract('.Js::from(['angioplasty' => route('procedures.latest-summary', 'angioplasty')]).', '.Js::from('f-angioplasty_coronersFor').')"', false)
        ->assertSee('x-data="procedureExtract('.Js::from(['rhc' => route('procedures.latest-summary', 'rhc')]).', '.Js::from('f-heart_katetrismFor').')"', false)
        // "Other" picks the procedure type to extract from.
        ->assertSee('aria-label="Procedure type"', false);

    $this->nurse->revokePermissionTo('angioplasty.view');
    $this->actingAs($this->nurse->fresh())->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()
        ->assertDontSee(route('procedures.latest-summary', 'angioplasty'));
});

test('Extract fetches the summary of the patient\'s latest study of the procedure type', function () {
    $patient = Patient::factory()->for($this->hospital)->create(['national_id' => '0012345678']);
    AngiographyStudy::factory()->for($patient)->create(['result_legacy' => '4', 'created_at' => now()->subWeek()]);
    $latest = AngiographyStudy::factory()->for($patient)->create(['result_legacy' => '6', 'DateOf_Date_of_angiography' => '1405/08/01', 'resulttt' => 'Candidate for CABG']);

    $study = $this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '0012345678']))
        ->assertOk()
        ->json('study');

    expect($study['id'])->toBe($latest->id)
        ->and($study['date'])->toBe('1405/08/01')
        ->and($study['status'])->toBe('draft')
        ->and($study['summary'])->toContain('Result: Three-vessel CAD (3VD) + Candidate for CABG')
        ->and($study['summary'])->not->toContain('One-vessel');

    // No study of that type for the patient, or no such patient.
    $this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angioplasty', 'nationalID' => '0012345678']))->assertOk()->assertJson(['study' => null]);
    $this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '0099999999']))->assertOk()->assertJson(['study' => null]);
    $this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '']))->assertStatus(422);

    $this->nurse->revokePermissionTo('angiography.view');
    $this->actingAs($this->nurse->fresh())->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '0012345678']))->assertForbidden();
});

test('Extract never reads another hospital\'s procedures', function () {
    $foreign = Patient::factory()->for(Hospital::factory())->create(['national_id' => '0012345678']);
    AngiographyStudy::factory()->for($foreign)->create(['result_legacy' => '6']);

    $this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '0012345678']))
        ->assertOk()
        ->assertJson(['study' => null]);

    // The same patient here: only this hospital's own study is found.
    $patient = Patient::factory()->for($this->hospital)->create(['national_id' => '0012345678']);
    $own = AngiographyStudy::factory()->for($patient)->create(['result_legacy' => '4', 'created_at' => now()->subYear()]);

    expect($this->actingAs($this->nurse)->getJson(route('procedures.latest-summary', ['angiography', 'nationalID' => '0012345678']))->json('study.id'))->toBe($own->id);
});

test('the discharge form folds each part of Others under its heading, one open at a time', function () {
    $html = $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()->getContent();
    expect($html)->toContain('x-data="{ part: null }"')
        ->toContain('class="collapse-trigger" :aria-expanded="part === 0" x-on:click="part = part === 0 ? null : 0">Other Non-invasive Tests</button>')
        ->toContain('>HF Specific</button>')
        ->and(substr_count($html, 'class="collapse-part"'))->toBe(11);

    // A part with an error opens.
    [$payload] = procedurePayload('discharge');
    $html = $this->actingAs($this->nurse)->from(startStudyUrl($this->nurse, 'discharge'))->followingRedirects()
        ->post('/dashboard/procedures/discharge', [...$payload, 'exercise_stress_test' => 'nope'])->getContent();
    expect($html)->toContain('x-data="{ part: 0 }"');
});

test('the final diagnosis list shows once three letters are typed', function () {
    $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()
        ->assertSee('<div x-show="searching" x-cloak', false)
        ->assertSee('name="final_diagnosis_codes[]"', false);
});

test('the discharge form groups its sections and leaves out risk factor dates and non-statin medication', function () {
    [$payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', $payload)->assertRedirect();
    $study = Procedures::model('discharge')::withoutGlobalScope(HospitalScope::class)->sole();
    // Imported from heart, these are kept although the form no longer asks for them.
    $study->forceFill(['DateOf_hxPrevMI_date' => '1400/01/01', 'hyperchol_tx_non_statin' => '1'])->save();

    $html = $this->actingAs($this->nurse)->get("/dashboard/procedures/discharge/{$study->id}/edit")->assertOk()->getContent();
    $abbrs = preg_match_all('/<span class="btn-abbr">([^<]*)<\/span>/', $html, $matches) ? $matches[1] : [];
    expect($abbrs)->toBe(['PI', 'RF', 'PSH', 'VS', 'PE', 'Px', 'Dx', 'ECG', 'Lab', 'Tx', 'Com', 'HC', 'Rec', 'Meds', 'DS', 'Others'])
        ->and($html)->toContain('title="Post Surgical History"')->toContain('title="Admission Vital Signs"')
        ->toContain('MI — Reperfusion Therapy')->toContain('HF Specific')->toContain('CABG — Operation Information')
        ->not->toContain('name="DateOf_hxPrevMI_date"')->not->toContain('name="hyperchol_tx_non_statin"');

    $this->actingAs($this->nurse)->put("/dashboard/procedures/discharge/{$study->id}", $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($study->fresh()->DateOf_hxPrevMI_date)->toBe('1400/01/01')
        ->and($study->fresh()->hyperchol_tx_non_statin)->toBe('1');
});

test('the discharge form is in English while its report keeps heart\'s Persian wording', function () {
    $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))
        ->assertOk()
        ->assertSee('title="Risk Factors &amp; Past Medical History"', false)
        ->assertSee('<span class="btn-abbr">RF</span>', false)
        ->assertSee('section-nav-container is-one-line', false)
        ->assertSee('Syncope')
        ->assertDontSee('سنکوب');

    $report = ProcedureReport::for('discharge', ['history_mode' => 'auto', 'clinicalcomplaint_syncope' => '1', 'disch_status' => '1', 'symptoms_others' => null]);
    $text = collect($report->sections())->flatMap(fn ($section) => array_column($section['lines'], 'text'))->implode(' ');
    expect($text)->toContain('سنکوب')->toContain('بهبودی کامل')->not->toContain('Syncope');
});

test('بومی / غیر بومی is a two-way toggle without heart\'s Unknown code', function () {
    // Two options and no forced dropdown, so choice-inputs.js draws a toggle.
    $html = $this->actingAs($this->nurse)->get(startStudyUrl($this->nurse, 'discharge'))->assertOk()->getContent();
    expect($html)->toMatch('/<select id="f-bomi"[^>]*>/')
        ->not->toMatch('/<select id="f-bomi"[^>]*data-widget="dropdown"/');
    expect(config('procedures.discharge.sections.0.fields.bomi.options'))->toBe(['1' => 'Local', '2' => 'Non-local'])
        ->and(config('procedures.discharge.sections.0.fields.bomi.fa_options'))->toBe(['1' => 'بومی', '2' => 'غیر بومی']);

    [$payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$payload, 'bomi' => '9'])->assertSessionHasErrors('bomi');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', [...$payload, 'bomi' => '2'])->assertSessionHasNoErrors();
});
