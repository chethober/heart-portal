<?php

use App\Enums\Role;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Scopes\HospitalScope;
use App\Models\User;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Nurse)->create();

    $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $this->study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
});

test('the dashboard opens the patient step of a new study', function () {
    $this->actingAs($this->tech)->get('/dashboard')->assertOk()
        ->assertSee('id="patientForm"', false)->assertDontSee('role="tablist"', false)
        ->assertSee('name="first_name"', false)->assertSee('name="last_name"', false)
        ->assertSee('name="nationalID"', false)->assertSee('name="dob"', false)
        ->assertSee('name="phone_number"', false)->assertSee('name="gender"', false)
        ->assertSee('name="study_date"', false)->assertSee('name="admission_number"', false)
        ->assertSee('value="echo"', false)->assertSee('value="angiography"', false)
        ->assertDontSee('name="Echo_indication"', false)->assertDontSee('name="lad_Vessel_patency"', false)
        ->assertDontSee('id="echoForm"', false)->assertDontSee('Rezaei');
});

test('staff who cannot record studies land on the worklist', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('worklist'));
});

test('the worklist lists studies with actions', function () {
    $this->actingAs($this->tech)->get('/dashboard/worklist')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee('Draft')
        ->assertSee(route('studies.show', $this->study), false)
        ->assertSee(route('studies.edit', $this->study), false)
        ->assertSee(route('studies.destroy', $this->study), false)
        ->assertViewHas('counts', fn ($counts) => $counts->all() === ['draft' => 1]);
});

test('the worklist filters by status and patient name', function () {
    $this->actingAs($this->tech)->get('/dashboard/worklist?status=final')->assertOk()->assertDontSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard/worklist?status=draft')->assertSee('Rezaei');
    $this->actingAs($this->tech)->get('/dashboard/worklist?search=reza')->assertSee('Rezaei')->assertSee('Clear filters');
    $this->actingAs($this->tech)->get('/dashboard/worklist?search=nobody')->assertDontSee('Rezaei');
});

test('old worklist filter links redirect to the worklist', function () {
    $this->actingAs($this->tech)->get('/dashboard?status=draft&search=reza')
        ->assertRedirect(route('worklist', ['status' => 'draft', 'search' => 'reza']));
});

test('the navbar holds the patient search and help, with no header row', function () {
    $this->actingAs($this->tech)->get('/dashboard')
        ->assertSee('id="nav-national-id"', false)
        ->assertSee('action="'.route('patients.index').'"', false)
        ->assertSee(route('help'), false)
        ->assertDontSee('<header', false);

    // Members of a hospital awaiting approval have nothing to search yet.
    $pending = User::factory()->for(Hospital::factory()->pending())->role(Role::HospitalAdmin)->create();
    $this->actingAs($pending)->get('/hospital/pending')->assertOk()->assertDontSee('id="nav-national-id"', false);
});

test('doctors can sign drafts from the worklist', function () {
    $doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();

    $this->actingAs($this->tech)->get('/dashboard/worklist')->assertDontSee(route('studies.finalize', $this->study), false);
    $this->actingAs($doctor)->get('/dashboard/worklist')->assertSee(route('studies.finalize', $this->study), false);
});

test('the patient echos section searches by national ID', function () {
    $this->actingAs($this->tech)->get('/dashboard/patients')->assertOk()->assertDontSee('Rezaei');

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=0012345678')
        ->assertOk()
        ->assertSee('Rezaei')
        ->assertSee(route('studies.show', $this->study), false);

    $this->actingAs($this->tech)->get('/dashboard/patients?nationalID=999')
        ->assertOk()
        ->assertSee('No echo reports found')
        ->assertSee('Record a new echo study');
});

test('the navigation only offers the sections a role may open', function () {
    $admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $platformAdmin = User::factory()->platformAdmin()->create();

    $this->actingAs($this->tech)->get('/dashboard/patients')
        ->assertSee(route('worklist'), false)
        ->assertDontSee(route('staff.index'), false)
        ->assertDontSee(route('audit.index'), false);

    $this->actingAs($admin)->get('/dashboard/staff')
        ->assertOk()
        ->assertSee(route('patients.index'), false)
        ->assertSee(route('audit.index'), false)
        ->assertDontSee(route('studies.create'), false);

    $this->actingAs($platformAdmin)->get('/dashboard')->assertRedirect(route('admin.hospitals.index'));
    $this->actingAs($platformAdmin)->get('/dashboard/hospitals')->assertOk()->assertDontSee(route('patients.index'), false);
});

test('old URLs redirect to their dashboard section', function (string $from, string $to) {
    $this->actingAs($this->tech)->get($from)->assertRedirect($to)->assertStatus(301);
})->with([
    ['/patients?nationalID=0012345678', '/dashboard/patients?nationalID=0012345678'],
    ['/studies/create', '/dashboard/studies/create'],
    ['/studies/7/edit', '/dashboard/studies/7/edit'],
    ['/staff', '/dashboard/staff'],
    ['/audit?action=study.viewed', '/dashboard/audit?action=study.viewed'],
    ['/admin/hospitals', '/dashboard/hospitals'],
    ['/profile', '/dashboard/profile'],
]);

test('the old patients tab link redirects to the patient echos section', function () {
    $this->actingAs($this->tech)->get('/dashboard?tab=patients&nationalID=0012345678')
        ->assertRedirect(route('patients.index', ['nationalID' => '0012345678']));
});

test('deleting a draft returns to the section it was deleted from', function () {
    $this->actingAs($this->tech)->delete("/dashboard/studies/{$this->study->id}", ['tab' => 'studies'])
        ->assertRedirect(route('worklist'));

    $second = $this->actingAs($this->tech)->postJson('/dashboard/studies', echoPayload())->json('id');

    $this->actingAs($this->tech)->delete("/dashboard/studies/{$second}")
        ->assertRedirect(route('patients.index', ['nationalID' => '0012345678']));
});

test('another hospital sees none of these studies on its dashboard', function () {
    $intruder = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($intruder)->get('/dashboard')->assertOk()->assertDontSee('Rezaei')->assertDontSee('0012345678');
    $this->actingAs($intruder)->get('/dashboard?patient='.$this->study->patient_id)->assertNotFound();

    $this->actingAs($intruder)->get('/dashboard/worklist')
        ->assertOk()
        ->assertDontSee('Rezaei')
        ->assertSee('No studies found')
        ->assertViewHas('counts', fn ($counts) => $counts->isEmpty());
    $this->actingAs($intruder)->get('/dashboard/worklist?search=Rezaei')->assertDontSee(route('studies.show', $this->study), false);
    $this->actingAs($intruder)->get('/dashboard/patients?nationalID=0012345678')->assertDontSee('Rezaei');
});

test('deactivated staff are logged out instead of seeing patient data', function () {
    $this->tech->forceFill(['is_active' => false])->save();

    $this->actingAs($this->tech)->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('patient demographics are completed once before choosing any study', function () {
    $admission = Admission::factory()->for($this->hospital)->for($this->study->patient)->create(['his_id' => 'ADM4455']);
    foreach (['echo', 'angiography', 'rhc', 'angioplasty', 'peripheral', 'intervention', 'discharge'] as $form) {
        $url = startStudyUrl($this->tech, $form, ['admission_number' => 'ADM4455']);
        $response = $this->get($url)->assertOk()
            ->assertSee('type="hidden" id="first_name" name="first_name" value="Ali"', false)
            ->assertSee('type="hidden" id="admission_id" name="admission_id" value="'.$admission->id.'"', false)
            ->assertDontSee('name="admission_number"', false)->assertDontSee('role="tablist"', false)
            ->assertDontSee('<label for="first_name">', false)->assertDontSee('<label for="echo_date">', false);
        $date = $form === 'echo' ? 'echo_date' : config("procedures.{$form}.date_field");
        $response->assertSee('type="hidden" id="'.$date.'" name="'.$date.'" value="1405/07/09"', false);
    }
});

test('the shared patient step creates demographics and admission before continuing', function () {
    $url = startStudyUrl($this->tech, overrides: ['admission_number' => 'NEW4455', 'first_name' => 'Updated']);
    $patient = Patient::whereNationalId('0012345678')->sole();
    expect($patient->first_name)->toBe('Updated');
    $admission = $patient->admissions()->where('his_id', 'NEW4455')->sole();
    $this->get($url)->assertOk()->assertViewHas('initialPatient', fn ($values) => $values['admission_id'] === $admission->id);
});

test('each started study keeps its own patient and date', function () {
    $first = startStudyUrl($this->tech, overrides: ['study_date' => '1405/07/01']);
    $second = startStudyUrl($this->tech, 'angiography', ['nationalID' => '9988776655', 'first_name' => 'Sara', 'study_date' => '1405/08/01']);
    $this->get($first)->assertOk()->assertViewHas('initialPatient', fn ($data) => $data['nationalID'] === '0012345678' && $data['echo_date'] === '1405/07/01');
    $this->get($second)->assertOk()->assertViewHas('values', fn ($data) => $data['nationalID'] === '9988776655' && $data['DateOf_Date_of_angiography'] === '1405/08/01');
});

test('another hospital cannot use a shared patient study context', function () {
    $url = startStudyUrl($this->tech);
    $other = User::factory()->role(Role::Nurse)->create();
    $this->actingAs($other)->get($url)->assertNotFound();
    $this->get(route('dashboard', ['patient' => $this->study->patient_id]))->assertNotFound();
});

test('the shared patient step rejects mismatched admissions and rolls back demographic changes', function () {
    $other = Patient::factory()->for($this->hospital)->create();
    Admission::factory()->for($this->hospital)->for($other)->create(['his_id' => 'TAKEN']);
    $this->actingAs($this->tech)->post(route('studies.start'), [
        ...$this->study->patient->toFormArray(), 'first_name' => 'Wrong', 'study' => 'echo',
        'admission_number' => 'TAKEN',
    ])->assertSessionHasErrors('admission_number');
    expect($this->study->patient->fresh()->first_name)->toBe('Ali');
});

test('the shared patient step validates dates and required fields', function () {
    $this->actingAs($this->tech)->post(route('studies.start'), [
        'study' => 'echo', 'study_date' => '1405/13/40',
    ])->assertSessionHasErrors(['first_name', 'last_name', 'nationalID', 'study_date']);
});

test('the shared patient step enforces study and admission permissions', function () {
    $this->tech->revokePermissionTo('echo.create');
    $this->actingAs($this->tech)->post(route('studies.start'), ['study' => 'echo'])->assertForbidden();
    $this->get('/dashboard')->assertOk()->assertDontSee('value="echo"', false)->assertSee('value="angiography"', false);
    $this->tech->revokePermissionTo('admission.create');
    $this->post(route('studies.start'), [
        ...$this->study->patient->toFormArray(), 'study' => 'angiography',
        'admission_number' => 'UNREGISTERED',
    ])->assertSessionHasErrors('admission_number');
});

test('starting a study opens its form as a full page under the patient taskbar', function () {
    $url = startStudyUrl($this->tech, 'angiography', ['admission_number' => 'ADM7788']);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect($url)->toStartWith(route('procedures.create', 'angiography'))->not->toContain('embedded');
    $this->get($url)->assertOk()->assertSee(route('patients.index'), false)
        ->assertSee('class="patient-bar-wrapper"', false)->assertSee('Ali Rezaei')->assertSee('New Patient')
        ->assertSee('href="'.e(route('dashboard', ['context' => $query['context']])).'"', false)
        ->assertDontSee('Change patient');

    // The current tab opens its patient step again, filled in; resubmitting replaces the tab.
    $this->get(route('dashboard', ['context' => $query['context']]))->assertOk()
        ->assertSee('value="0012345678"', false)->assertSee('value="ADM7788"', false)->assertSee('value="1405/07/09"', false)
        ->assertSee('name="replaces" value="'.$query['context'].'"', false);
    $replaced = startStudyUrl($this->tech, 'echo', ['replaces' => $query['context']]);
    $this->get($url)->assertNotFound();
    $this->get($replaced)->assertOk()->assertSee('Echocardiography')->assertDontSee('Coronary Angiography');
});

test('the taskbar keeps a tab for each started study until it is closed', function () {
    $first = startStudyUrl($this->tech);
    $second = startStudyUrl($this->tech, 'angiography', ['nationalID' => '9988776655', 'first_name' => 'Sara']);
    // Starting the same patient's form again reuses their tab.
    $again = startStudyUrl($this->tech, overrides: ['study_date' => '1405/08/01']);
    $this->get($first)->assertNotFound();

    $page = $this->get($second)->assertOk();
    $page->assertSee('href="'.e($again).'"', false)->assertSee('Sara Rezaei');
    expect(substr_count($page->getContent(), 'class="patient-tab-close"'))->toBe(2);

    parse_str(parse_url($second, PHP_URL_QUERY), $query);
    $this->from($second)->delete(route('studies.close', $query['context']), ['current' => $query['context']])->assertRedirect($again);
    $this->get('/dashboard')->assertOk()->assertDontSee('Sara Rezaei')->assertSee('Ali Rezaei');
});

test('saving a study closes its taskbar tab', function () {
    $echo = startStudyUrl($this->tech);
    parse_str(parse_url($echo, PHP_URL_QUERY), $query);
    $this->postJson(route('studies.store', ['context' => $query['context']]), [
        'first_name' => 'Ali', 'last_name' => 'Rezaei', 'nationalID' => '0012345678', 'echo_date' => '1405/07/09',
    ])->assertCreated();
    $this->get($echo)->assertNotFound();
    $this->get('/dashboard')->assertOk()->assertDontSee('class="patient-tab-close"', false);
});

test('another member sees none of my taskbar tabs', function () {
    startStudyUrl($this->tech);
    $colleague = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
    $this->actingAs($colleague)->get('/dashboard')->assertOk()->assertDontSee('class="patient-tab-close"', false);
});

test('opening a study form without step 1 goes to the patient step', function () {
    $this->actingAs($this->tech)->get(route('procedures.create', 'angiography'))
        ->assertRedirect(route('dashboard'));
});
