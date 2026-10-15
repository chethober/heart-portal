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

test('doctors upload a private signature that colleagues can see but other hospitals cannot', function () {
    $this->actingAs($this->doctor)
        ->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('sig.png', 400, 120)])
        ->assertRedirect();

    $path = $this->doctor->fresh()->signature_path;
    Storage::disk('local')->assertExists($path);

    $colleague = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->actingAs($colleague)->get("/dashboard/users/{$this->doctor->id}/signature")->assertOk();

    $stranger = User::factory()->role(Role::Doctor)->create();
    $this->actingAs($stranger)->get("/dashboard/users/{$this->doctor->id}/signature")->assertNotFound();
});

test('technicians cannot upload signatures and non-images are rejected', function () {
    $tech = User::factory()->for($this->hospital)->role(Role::Nurse)->create();
    $this->actingAs($tech)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('s.png')])->assertForbidden();

    $this->actingAs($this->doctor)
        ->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml')])
        ->assertSessionHasErrors('signature', null, 'signature');
});

/**
 * A blank image as the canvas would send it (or, for the tests that expect a rejection, a JPEG).
 */
function signatureImage(int $width, int $height, string $type = 'png'): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    $type === 'png' ? imagepng($image) : imagejpeg($image);

    return (string) ob_get_clean();
}

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
    $this->actingAs($this->doctor)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('a.png', 300, 100)]);
    $original = $this->doctor->fresh()->signature_path;

    $this->actingAs($this->doctor)->postJson('/dashboard/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    $this->actingAs($this->doctor)->post("/dashboard/studies/{$study->id}/finalize");

    $this->actingAs($this->doctor)->post('/dashboard/profile/signature', ['signature' => UploadedFile::fake()->image('b.png', 300, 100)]);

    $version = $study->versions()->sole();
    expect($version->signature_path)->toBe($original);
    Storage::disk('local')->assertExists($original);

    $this->actingAs($this->doctor)->get("/dashboard/studies/{$study->id}")
        ->assertSee(route('users.signature', [$this->doctor, 'version' => $version->id]), false);
    $this->actingAs($this->doctor)->get("/dashboard/users/{$this->doctor->id}/signature?version={$version->id}")->assertOk();
});
