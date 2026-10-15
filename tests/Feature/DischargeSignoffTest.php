<?php

use App\Enums\Role;
use App\Enums\StudyStatus;
use App\Models\DischargeStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The discharge summary is signed by the intern (کارورز), then the resident (دستیار), then the
 * attending physician (پزشک معالج); each may change the summary before signing it.
 */
beforeEach(function () {
    Storage::fake('local');
    $this->hospital = Hospital::factory()->create();
    $this->nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->intern = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr Intern']);
    $this->resident = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr Resident']);
    $this->attending = User::factory()->for($this->hospital)->role(Role::Doctor)->create(['name' => 'Dr Attending']);

    [$this->payload] = procedurePayload('discharge');
    $this->actingAs($this->nurse)->post('/dashboard/procedures/discharge', $this->payload)->assertRedirect();
    $this->study = DischargeStudy::withoutGlobalScope(HospitalScope::class)->sole();
    $this->url = "/dashboard/procedures/discharge/{$this->study->id}";
});

test('the discharge summary is signed by the intern, the resident and then the attending physician', function () {
    $this->actingAs($this->intern)->get($this->url)->assertSee('Sign as Intern');
    $this->actingAs($this->intern)->post("{$this->url}/finalize")->assertRedirect()
        ->assertSessionHas('status', 'Signed as Intern. Next to sign: Resident.');

    $study = $this->study->fresh();
    expect($study->status)->toBe(StudyStatus::Draft)
        ->and($study->intern_signed_by)->toBe($this->intern->id)
        ->and($study->nextSignoff())->toBe('resident')
        ->and($study->versions()->count())->toBe(0);

    $this->actingAs($this->resident)->get($this->url)
        ->assertSee('Sign as Resident')
        ->assertSee('Dr Intern')
        ->assertSee('DRAFT — AWAITING SIGNATURES');
    $this->actingAs($this->resident)->get('/dashboard/worklist')->assertSee('Sign as Resident');
    $this->actingAs($this->resident)->post("{$this->url}/finalize")->assertRedirect();

    $this->actingAs($this->attending)->get($this->url)->assertSee('Sign as Attending physician &amp; Finalize', false);
    $this->actingAs($this->attending)->post("{$this->url}/finalize")->assertRedirect()
        ->assertSessionHas('status', 'Report signed and finalized.');

    $study = $this->study->fresh();
    expect($study->status)->toBe(StudyStatus::Final)
        ->and($study->resident_signed_by)->toBe($this->resident->id)
        ->and($study->reported_by)->toBe($this->attending->id)
        ->and($study->versions()->sole()->signed_by)->toBe($this->attending->id);

    $this->actingAs($this->attending)->get($this->url)->assertSee('Dr Intern')->assertSee('Dr Resident')->assertSee('Dr Attending');
    $this->actingAs($this->attending)->post("{$this->url}/finalize")->assertForbidden();
});

test('the next doctor may change the summary the last one signed', function () {
    $this->actingAs($this->intern)->post("{$this->url}/finalize");

    // Once signed, the nurse no longer edits it and the draft cannot be deleted.
    $this->actingAs($this->nurse)->put($this->url, [...$this->payload, 'comment' => 'Nurse change'])->assertForbidden();
    $this->actingAs($this->nurse)->delete($this->url)->assertForbidden();

    $this->actingAs($this->resident)->get("{$this->url}/edit")->assertOk();
    $this->actingAs($this->resident)->put($this->url, [...$this->payload, 'comment' => 'Resident corrected the course'])->assertRedirect();

    $study = $this->study->fresh();
    expect($study->comment)->toBe('Resident corrected the course')
        ->and($study->status)->toBe(StudyStatus::Draft)
        ->and($study->intern_signed_by)->toBe($this->intern->id);
});

test('one doctor may sign more than one step', function () {
    $this->actingAs($this->resident)->post("{$this->url}/finalize");
    $this->actingAs($this->resident)->post("{$this->url}/finalize");
    $this->actingAs($this->attending)->post("{$this->url}/finalize");

    $study = $this->study->fresh();
    expect($study->intern_signed_by)->toBe($this->resident->id)
        ->and($study->resident_signed_by)->toBe($this->resident->id)
        ->and($study->status)->toBe(StudyStatus::Final);
});

test('nurses cannot sign a step', function () {
    $this->actingAs($this->nurse)->post("{$this->url}/finalize")->assertForbidden();

    expect($this->study->fresh()->intern_signed_at)->toBeNull();
});

test('each step keeps the signature it was signed with', function () {
    $this->actingAs($this->intern)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('a.png', 300, 100)]);
    $original = $this->intern->fresh()->signature_path;
    $this->actingAs($this->intern)->post("{$this->url}/finalize");
    $this->actingAs($this->intern)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('b.png', 300, 100)]);

    $image = route('users.signature', [$this->intern, 'discharge' => $this->study->id, 'step' => 'intern']);
    expect($this->study->fresh()->intern_signature_path)->toBe($original);
    $this->actingAs($this->resident)->get($this->url)->assertSee(e($image), false);
    $this->actingAs($this->resident)->get($image)->assertOk();
    $this->actingAs($this->resident)->get(route('users.signature', [$this->intern, 'discharge' => $this->study->id, 'step' => 'resident']))->assertNotFound();
    $this->actingAs($this->resident)->get(route('users.signature', [$this->intern, 'discharge' => $this->study->id, 'step' => 'nope']))->assertNotFound();
});

test('another hospital cannot sign a step or see its signature', function () {
    $this->actingAs($this->intern)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('a.png', 300, 100)]);
    $this->actingAs($this->intern)->post("{$this->url}/finalize");
    $outsider = User::factory()->for(Hospital::factory())->role(Role::Doctor)->create();

    $this->actingAs($outsider)->post("{$this->url}/finalize")->assertNotFound();
    $this->actingAs($outsider)->put($this->url, $this->payload)->assertNotFound();
    $this->actingAs($outsider)->get(route('users.signature', [$this->intern, 'discharge' => $this->study->id, 'step' => 'intern']))->assertNotFound();

    expect($this->study->fresh()->resident_signed_at)->toBeNull();
});
