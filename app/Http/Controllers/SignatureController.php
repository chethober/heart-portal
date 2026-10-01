<?php

namespace App\Http\Controllers;

use App\Models\EchoStudyVersion;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Doctors' signature images. Stored on the private disk and only served to
 * staff of the same hospital, so they cannot be fetched by URL guessing.
 */
class SignatureController extends Controller
{
    /**
     * The user's current signature, or with ?version= the one a signed report version was signed with.
     */
    public function show(Request $request, User $user): StreamedResponse
    {
        abort_unless($user->hospital_id !== null && $user->hospital_id === $request->user()->hospital_id, 404);

        $path = $request->filled('version')
            ? EchoStudyVersion::where('signed_by', $user->id)->findOrFail($request->integer('version'))->signature_path
            : $user->signature_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validateWithBag('signature', [
            'signature' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=2000,max_height=1000'],
        ]);

        $user = $request->user();
        // Earlier files are kept: signed report versions still show the signature they were signed with.
        $path = $request->file('signature')->store("signatures/{$user->hospital_id}", 'local');

        $user->forceFill(['signature_path' => $path])->save();
        Audit::record('user.signature_updated', $user);

        return redirect()->to(route('profile.edit').'#signature')->with('status', 'signature-updated');
    }
}
