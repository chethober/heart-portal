<?php

namespace App\Support;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (authenticator app) second factor with one-time recovery codes.
 */
class TwoFactor
{
    public function __construct(private Google2FA $engine) {}

    /**
     * Start enrolment: a new secret and recovery codes, unconfirmed until a code is verified.
     */
    public function enable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->engine->generateSecretKey(32),
            'two_factor_recovery_codes' => $this->newRecoveryCodes(),
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_timestep' => null,
        ])->save();
    }

    /**
     * Accepts a current TOTP code once; the same code cannot be reused.
     */
    public function verify(User $user, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if ($user->two_factor_secret === null || ! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timestep = $this->engine->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_timestep, 1);

        if ($timestep === false) {
            return false;
        }

        $user->forceFill(['two_factor_last_timestep' => $timestep === true ? $this->engine->getTimestamp() : $timestep])->save();

        return true;
    }

    /**
     * Accepts an unused recovery code and burns it.
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $codes = collect($user->two_factor_recovery_codes ?? []);
        $match = $codes->first(fn (string $stored) => hash_equals($stored, trim($code)));

        if ($match === null) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => $codes->reject(fn ($c) => $c === $match)->values()->all()])->save();

        return true;
    }

    public function regenerateRecoveryCodes(User $user): void
    {
        $user->forceFill(['two_factor_recovery_codes' => $this->newRecoveryCodes()])->save();
    }

    public function qrCodeSvg(User $user): string
    {
        $url = $this->engine->getQRCodeUrl(config('auth.two_factor.issuer'), $user->email, $user->two_factor_secret);

        $svg = (new Writer(new ImageRenderer(new RendererStyle(192, 0), new SvgImageBackEnd)))->writeString($url);

        // Drop the XML declaration so the SVG can be inlined in HTML.
        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * @return list<string>
     */
    private function newRecoveryCodes(): array
    {
        return Collection::times(8, fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();
    }
}
