<?php

namespace App\Http\Controllers;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use App\Models\Scopes\HospitalScope;
use App\Support\Audit;
use Illuminate\Http\Response;

/**
 * The public page the printed prescription's QR code opens, so a pharmacy can check that a paper
 * prescription is genuine, unchanged and not cancelled. It is reached only through the random token
 * in the QR code and shows no patient details.
 */
class PrescriptionVerificationController extends Controller
{
    public function __invoke(string $token): Response
    {
        // Public page: there is no signed-in hospital, so the tenant scope is lifted for this lookup only.
        $prescription = Prescription::withoutGlobalScope(HospitalScope::class)
            ->where('verification_token', $token)
            ->whereIn('status', [PrescriptionStatus::Signed->value, PrescriptionStatus::Cancelled->value])
            ->with(['hospital', 'signer', 'items' => fn ($items) => $items->withoutGlobalScope(HospitalScope::class)])
            ->firstOrFail();

        Audit::record('prescription.verified', $prescription);

        return response()
            ->view('prescriptions.verify', ['prescription' => $prescription])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
