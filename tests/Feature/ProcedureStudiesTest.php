<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use App\Support\Procedures;

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

    return [[
        'nationalID' => '0012345678',
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        config("procedures.{$procedure}.date_field") => '1405/07/09',
        $name => $value,
    ], $field['type'] === 'choice' ? $field['options'][$value] : $value];
}

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
});

test('each heart form records, reports and signs a study', function (string $procedure) {
    [$payload, $label] = procedurePayload($procedure);

    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/create")
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
    $this->actingAs($this->doctor)->post("/dashboard/procedures/{$procedure}/{$study->id}/finalize")->assertRedirect();

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

test('the New Study menu lists echo and every heart form', function () {
    $response = $this->actingAs($this->nurse)->get('/dashboard/patients')->assertOk()->assertSee(route('studies.create'));

    foreach (array_keys(Procedures::MODELS) as $procedure) {
        $response->assertSee(route('procedures.create', $procedure))->assertSee(Procedures::label($procedure));
    }
});

test('staff who cannot record studies get no New Study menu', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get('/dashboard/patients')->assertOk()->assertDontSee(route('procedures.create', 'angiography'));
});

test('each heart form previews its report live without saving', function (string $procedure) {
    [$payload, $label] = procedurePayload($procedure);

    $this->actingAs($this->nurse)->get("/dashboard/procedures/{$procedure}/create")
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
