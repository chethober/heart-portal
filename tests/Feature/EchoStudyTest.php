<?php

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
});

function echoPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'nationalID' => '۰۰۱۲۳۴۵۶۷۸',
        'dob' => '1360/02/15',
        'gender' => 'Male',
        'echo_date' => '1405/07/09',
        'Height' => 180,
        'Weight' => 80,
        'lv_EF' => 35,
        'lv_LVEDV' => 150,
        'lv_size' => 'Auto',
        'lv_rwma_checked' => '1',
        'other_des_lv' => 'apical thinning <script>alert(1)</script>',
        ...$overrides,
    ];
}

test('technician saves a study and the patient is created with encrypted identifiers', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())
        ->assertCreated()
        ->assertJson(['success' => true]);

    $patient = Patient::withoutGlobalScope(HospitalScope::class)->sole();
    expect($patient->hospital_id)->toBe($this->hospital->id)
        ->and($patient->national_id)->toBe('0012345678');

    $raw = DB::table('patients')->first();
    expect($raw->national_id)->not->toContain('0012345678');

    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->created_by)->toBe($this->tech->id)
        ->and($study->lv_EF)->toBe(35.0)
        ->and($study->lv_rwma_checked)->toBeTrue();
});

test('a second study reuses the patient and keeps demographics current', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['nationalID' => '0012345678', 'phone_number' => '09120000000', 'dob' => null]))->assertCreated();

    $patient = Patient::withoutGlobalScope(HospitalScope::class)->sole();
    expect($patient->studies()->count())->toBe(2)
        ->and($patient->phone_number)->toBe('09120000000')
        ->and($patient->dob)->toBe('1360/02/15');
});

test('unknown fields and malformed values are rejected', function () {
    $this->actingAs($this->tech)
        ->postJson('/dashboard/studies', echoPayload(['id) VALUES (1); DROP TABLE patients; --' => 'x', 'lv_EF' => 'lots']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lv_EF']);

    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['hospital_id' => 999]))
        ->assertJsonValidationErrors(['hospital_id']);

    // The reporting doctor is whoever signs, so it cannot be typed in.
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['doctor' => 'Dr. Karimi']))
        ->assertJsonValidationErrors(['doctor']);

    expect(EchoStudy::withoutGlobalScope(HospitalScope::class)->count())->toBe(0);
});

test('required patient fields are enforced', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['first_name' => '', 'nationalID' => '']))
        ->assertJsonValidationErrors(['first_name', 'nationalID']);
});

test('history and report render with derived values and sanitized free text', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee(route('studies.show', $study), false);

    $this->actingAs($this->tech)->get("/dashboard/studies/{$study->id}")
        ->assertOk()
        ->assertSee($this->hospital->name)
        ->assertSeeText('There is mild LV enlargement')
        ->assertSee('apical thinning', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        // The report opens inside the dashboard, with its navigation and no old navbar.
        ->assertSee('class="echo-report"', false)
        ->assertSee('id="nav-national-id"', false)
        ->assertDontSee('app-navbar', false);
});

test('live preview renders unsaved form data on a bare page', function () {
    $this->actingAs($this->tech)
        ->post('/dashboard/studies/preview', [...echoPayload(), 'lv_size' => 'Manual', 'lv_size_manual' => 'Borderline LV size'])
        ->assertOk()
        ->assertSee('Borderline LV size')
        ->assertDontSee('id="nav-national-id"', false)
        ->assertDontSee('DRAFT — NOT SIGNED');
});

test('hospitals cannot see each others patients or studies', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();

    $intruder = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($intruder)->get("/dashboard/studies/{$study->id}")->assertNotFound();
    $this->actingAs($intruder)->delete("/dashboard/studies/{$study->id}")->assertNotFound();
    $this->actingAs($intruder)->get('/dashboard/patients?nationalID=0012345678')->assertOk()->assertDontSee('Rezaei');

    // Same national ID in another hospital creates a separate patient record.
    $this->actingAs($intruder)->postJson('/dashboard/studies', echoPayload(['first_name' => 'Other']))->assertCreated();
    expect(Patient::withoutGlobalScope(HospitalScope::class)->count())->toBe(2);
    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')->assertDontSee('Other');
});

test('hospital admins can view but not create studies', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();

    $this->actingAs($admin)->get('/dashboard/studies/create')->assertForbidden();
    $this->actingAs($admin)->postJson('/dashboard/studies', echoPayload())->assertForbidden();
    $this->actingAs($admin)->get('/dashboard/patients')->assertOk()->assertDontSee(route('studies.create'), false);
});

test('form page loads', function () {
    // The form lives inside the dashboard: its navigation is shown and the old navbar is gone.
    $this->actingAs($this->tech)->get(startStudyUrl($this->tech))
        ->assertOk()
        ->assertSee('echoForm', false)
        // The section titles sit on one line, scrolling sideways when the screen is too narrow.
        ->assertSee('section-nav-container is-one-line', false)
        ->assertSee(route('patients.index'), false)
        ->assertDontSee('app-navbar', false)
        ->assertDontSee('id="doctor"', false);
});

test('abnormal septal motion due to LBBB is saved and reported in the LV wall motion', function () {
    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload(['lv_rwma_checked' => null, 'lv_septal_motion_lbbb' => '1']))->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    expect($study->lv_septal_motion_lbbb)->toBeTrue();

    $this->actingAs($this->tech)->get("/dashboard/studies/{$study->id}")->assertOk()
        ->assertSeeText('LV Wall Motion')
        ->assertSeeText('There is abnormal septal motion due to LBBB.')
        ->assertDontSeeText('There is no RWMA.');

    // With RWMA ticked and no abnormal segment, both are reported.
    $study->update(['lv_rwma_checked' => true]);
    $this->actingAs($this->tech)->get("/dashboard/studies/{$study->id}")->assertOk()
        ->assertSeeText('There is no RWMA. There is abnormal septal motion due to LBBB.');
});

test('the echo form lays out indication, body size, valves and pericardium as asked', function () {
    $html = $this->actingAs($this->tech)->get(startStudyUrl($this->tech))->assertOk()->getContent();
    $at = fn (string $needle) => strpos($html, $needle);

    // Echo indication is the first field of the clinical details, in two rows of four small
    // fields (Echo indication, BP, HR, Rhythm / Height, Weight, O2 sat, Echo type), then the history.
    $order = ['Echo_indication', 'BP', 'HR', 'Rhythm', 'Height', 'Weight', 'O2_sat', 'Echo_type', 'History_diseases'];
    $positions = array_map(fn (string $id) => $at('id="'.$id.'"'), $order);
    expect($positions)->toBe(collect($positions)->sort()->values()->all())
        ->and(substr_count(substr($html, $positions[0], $positions[8] - $positions[0]), '<div class="row"'))->toBe(0);

    // The LBBB box is beside RWMA and global hypokinesia.
    expect($at('id="lv_septal_motion_lbbb"'))->toBeGreaterThan($at('id="lv_global_hypokinesia"'))
        ->and($at('id="lv_septal_motion_lbbb"'))->toBeLessThan($at('id="rwma-segments"'));

    // Each valve: its type and the abnormality description on one row, the measurements on the next.
    foreach (['mv' => 'mv_PE_Velocity', 'av' => 'av_peakvelocity', 'pv' => 'pv_stenosis1', 'tv' => 'tv_PE_Velocity'] as $valve => $first) {
        expect($html)->toMatch('/name="'.$valve.'_abnormality_description" type="text" \/>\s*<\/div>\s*<\/div>\s*<div class="row">\s*<div class="field">\s*<label for="'.$first.'">/')
            ->and($html)->not->toContain('id="'.$valve.'_abnormality_container" data-follows');
    }
    // The pulmonary valve's velocity follows its stenosis and regurgitation.
    expect($at('id="pv_stenosis1"'))->toBeLessThan($at('id="pv_regurgitant"'))
        ->and($at('id="pv_regurgitant"'))->toBeLessThan($at('id="pv_PV_velocity"'))
        ->and($at('id="pv_PV_velocity"'))->toBeLessThan($at('id="pv_PV_ACCT"'));

    // The pericardium's choices are dropdowns.
    foreach (['pre_Morphology', 'pre_Pericardial_effusion', 'pre_Hemodynamic'] as $name) {
        expect($html)->toContain('<select id="'.$name.'" name="'.$name.'" data-widget="dropdown">');
    }
});
