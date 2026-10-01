<?php

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->hospital = Hospital::factory()->create();
    $this->doctor = User::factory()->for($this->hospital)->role(Role::Doctor)->create();
});

test('doctors upload a private signature that colleagues can see but other hospitals cannot', function () {
    $this->actingAs($this->doctor)
        ->post('/profile/signature', ['signature' => UploadedFile::fake()->image('sig.png', 400, 120)])
        ->assertRedirect();

    $path = $this->doctor->fresh()->signature_path;
    Storage::disk('local')->assertExists($path);

    $colleague = User::factory()->for($this->hospital)->role(Role::Technician)->create();
    $this->actingAs($colleague)->get("/users/{$this->doctor->id}/signature")->assertOk();

    $stranger = User::factory()->role(Role::Doctor)->create();
    $this->actingAs($stranger)->get("/users/{$this->doctor->id}/signature")->assertNotFound();
});

test('technicians cannot upload signatures and non-images are rejected', function () {
    $tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
    $this->actingAs($tech)->post('/profile/signature', ['signature' => UploadedFile::fake()->image('s.png')])->assertForbidden();

    $this->actingAs($this->doctor)
        ->post('/profile/signature', ['signature' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml')])
        ->assertSessionHasErrors('signature', null, 'signature');
});

test('a signed version keeps the signature it was signed with', function () {
    $this->actingAs($this->doctor)->post('/profile/signature', ['signature' => UploadedFile::fake()->image('a.png', 300, 100)]);
    $original = $this->doctor->fresh()->signature_path;

    $this->actingAs($this->doctor)->postJson('/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    $this->actingAs($this->doctor)->post("/studies/{$study->id}/finalize");

    $this->actingAs($this->doctor)->post('/profile/signature', ['signature' => UploadedFile::fake()->image('b.png', 300, 100)]);

    $version = $study->versions()->sole();
    expect($version->signature_path)->toBe($original);
    Storage::disk('local')->assertExists($original);

    $this->actingAs($this->doctor)->get("/studies/{$study->id}")
        ->assertSee(route('users.signature', [$this->doctor, 'version' => $version->id]), false);
    $this->actingAs($this->doctor)->get("/users/{$this->doctor->id}/signature?version={$version->id}")->assertOk();
});
