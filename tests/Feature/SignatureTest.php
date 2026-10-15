<?php

use App\Enums\Role;
use App\Models\AuditLog;
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

test('signatures are drawn: an uploaded image file is not accepted', function () {
    $this->actingAs($this->doctor)
        ->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('sig.png', 400, 120)])
        ->assertSessionHasErrors('drawn_signature', null, 'signature');

    expect($this->doctor->fresh()->signature_path)->toBeNull();
    $this->actingAs($this->doctor)->get('/dashboard/profile')->assertDontSee('type="file"', false);
});

test('nurses cannot save a signature', function () {
    $nurse = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->actingAs($nurse)->post('/dashboard/profile/signature', drawnSignature())->assertForbidden();
});

test('doctors can save a signature drawn on the canvas, visible to colleagues only', function () {
    $png = signatureImage(600, 180);

    $this->actingAs($this->doctor)
        ->post('/dashboard/profile/signature', ['drawn_signature' => 'data:image/png;base64,'.base64_encode($png)])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit').'#signature');

    $path = $this->doctor->fresh()->signature_path;
    expect($path)->toStartWith("signatures/{$this->hospital->id}/")->toEndWith('.png');
    expect(Storage::disk('local')->get($path))->toBe($png);
    expect(AuditLog::where('action', 'user.signature_updated')->sole()->changes)->toBe(['method' => 'drawn']);

    $this->actingAs(User::factory()->for($this->hospital)->role(Role::Nurse)->create())
        ->get("/dashboard/users/{$this->doctor->id}/signature")->assertOk();
    $this->actingAs(User::factory()->role(Role::Doctor)->create())
        ->get("/dashboard/users/{$this->doctor->id}/signature")->assertNotFound();
});

test('drawn signatures must be real PNGs within the size limits', function (string $dataUrl) {
    $this->actingAs($this->doctor)
        ->post('/dashboard/profile/signature', ['drawn_signature' => $dataUrl])
        ->assertSessionHasErrors('drawn_signature', null, 'signature');

    expect($this->doctor->fresh()->signature_path)->toBeNull();
})->with([
    'not base64' => 'data:image/png;base64,***',
    'not an image' => 'data:image/png;base64,'.base64_encode('<svg onload="alert(1)"></svg>'),
    'svg data url' => 'data:image/svg+xml;base64,'.base64_encode('<svg></svg>'),
    'jpeg disguised as png' => fn () => 'data:image/png;base64,'.base64_encode(signatureImage(300, 100, 'jpeg')),
    'too large' => fn () => 'data:image/png;base64,'.base64_encode(signatureImage(2400, 400)),
]);

test('a signed version keeps the signature it was signed with', function () {
    $this->actingAs($this->doctor)->post('/dashboard/profile/signature', drawnSignature());
    $original = $this->doctor->fresh()->signature_path;

    $this->actingAs($this->doctor)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    $this->actingAs($this->doctor)->post("/dashboard/studies/{$study->id}/finalize");

    $this->actingAs($this->doctor)->post('/dashboard/profile/signature', drawnSignature());

    $version = $study->versions()->sole();
    expect($version->signature_path)->toBe($original);
    Storage::disk('local')->assertExists($original);

    $this->actingAs($this->doctor)->get("/dashboard/studies/{$study->id}")
        ->assertSee(route('users.signature', [$this->doctor, 'version' => $version->id]), false);
    $this->actingAs($this->doctor)->get("/dashboard/users/{$this->doctor->id}/signature?version={$version->id}")->assertOk();
});
