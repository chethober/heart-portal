<?php

namespace App\Http\Controllers;

use App\Models\DischargeStudy;
use App\Models\EchoStudyVersion;
use App\Models\ProcedureStudyVersion;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Doctors' signature images. Stored on the private disk and only served to
 * staff of the same hospital, so they cannot be fetched by URL guessing.
 */
class SignatureController extends Controller
{
    /**
     * The user's current signature, or with ?version= (echo) or ?procedure_version= the one a signed
     * report version was signed with, or with ?discharge=&step= the one a discharge summary's
     * sign-off step (intern, resident) was signed with.
     */
    public function show(Request $request, User $user): StreamedResponse
    {
        abort_unless($user->hospital_id !== null && $user->hospital_id === $request->user()->hospital_id, 404);

        $path = match (true) {
            $request->filled('version') => EchoStudyVersion::where('signed_by', $user->id)->findOrFail($request->integer('version'))->signature_path,
            // Signed versions of the procedures ported from heart (angiography, ...).
            $request->filled('procedure_version') => ProcedureStudyVersion::where('signed_by', $user->id)->findOrFail($request->integer('procedure_version'))->signature_path,
            $request->filled('discharge') => $this->signoffPath($user, $request->integer('discharge'), $request->string('step')->toString()),
            default => $user->signature_path,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function signoffPath(User $user, int $study, string $step): ?string
    {
        abort_unless(array_key_exists($step, DischargeStudy::SIGNOFFS), 404);

        return DischargeStudy::where("{$step}_signed_by", $user->id)->findOrFail($study)->getAttribute("{$step}_signature_path");
    }

    /**
     * Saves the signature drawn on the profile page's canvas (sent as a PNG data URL in
     * `drawn_signature`). Signatures are drawn, not uploaded as image files.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validateWithBag('signature', [
            'drawn_signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:1400000'],
        ]);

        $user = $request->user();

        // Earlier files are kept: signed report versions still show the signature they were signed with.
        $path = "signatures/{$user->hospital_id}/".Str::random(40).'.png';
        Storage::disk('local')->put($path, $this->decodeDrawnSignature($request->string('drawn_signature')->toString()));

        $user->forceFill(['signature_path' => $path])->save();
        Audit::record('user.signature_updated', $user, ['method' => 'drawn']);

        return redirect()->to(route('profile.edit').'#signature')->with('status', 'signature-updated');
    }

    /**
     * The canvas sends a data URL; accept it only if it really is a PNG within the size limits.
     *
     * @throws ValidationException
     */
    private function decodeDrawnSignature(string $dataUrl): string
    {
        $png = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        $size = $png === false || strlen($png) > 1024 * 1024 ? false : @getimagesizefromstring($png);

        if ($png === false || $size === false || $size[2] !== IMAGETYPE_PNG || $size[0] > 2000 || $size[1] > 1000) {
            throw ValidationException::withMessages(['drawn_signature' => __('The drawn signature could not be read. Clear it and sign again.')])
                ->errorBag('signature');
        }

        return $png;
    }
}
