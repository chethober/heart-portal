<?php

use App\Enums\Role;
use App\Models\User;
use App\Support\TwoFactor;
use PragmaRX\Google2FA\Google2FA;

function currentOtp(User $user, int $offsetSteps = 0): string
{
    $engine = app(Google2FA::class);

    return $engine->oathTotp($user->fresh()->two_factor_secret, $engine->getTimestamp() + $offsetSteps);
}

beforeEach(function () {
    $this->user = User::factory()->role(Role::Doctor)->create();
});

test('a user can enrol an authenticator app and gets recovery codes', function () {
    $this->actingAs($this->user)->post('/profile/two-factor', ['password' => 'password'])->assertRedirect();
    $this->actingAs($this->user)->get('/profile')->assertSee('<svg', false)->assertSee('Setup key');

    $this->actingAs($this->user)->post('/profile/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code', null, 'twoFactor');
    $this->actingAs($this->user)->post('/profile/two-factor/confirm', ['code' => currentOtp($this->user)])->assertSessionHas('showRecoveryCodes');

    $user = $this->user->fresh();
    expect($user->hasEnabledTwoFactor())->toBeTrue()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8);

    // Secrets are stored encrypted.
    expect(DB::table('users')->where('id', $user->id)->value('two_factor_secret'))->not->toBe($user->two_factor_secret);
});

test('login with two-factor requires a valid, unused code', function () {
    app(TwoFactor::class)->enable($this->user);
    $this->user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    $this->post('/two-factor-challenge', ['code' => '123456'])->assertSessionHasErrors('code');
    $this->assertGuest();

    $code = currentOtp($this->user);
    $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($this->user);

    // The same code cannot be replayed for a second login.
    auth()->logout();
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);
    $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('a recovery code works exactly once', function () {
    app(TwoFactor::class)->enable($this->user);
    $this->user->forceFill(['two_factor_confirmed_at' => now()])->save();
    $recovery = $this->user->fresh()->two_factor_recovery_codes[0];

    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);
    $this->post('/two-factor-challenge', ['recovery_code' => $recovery]);
    $this->assertAuthenticatedAs($this->user);
    expect($this->user->fresh()->two_factor_recovery_codes)->toHaveCount(7);

    auth()->logout();
    $this->post('/login', ['email' => $this->user->email, 'password' => 'password']);
    $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertSessionHasErrors('recovery_code');
    $this->assertGuest();
});

test('the challenge page cannot be used without a password step', function () {
    $this->get('/two-factor-challenge')->assertRedirect(route('login'));
    $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect(route('login'));
});

test('when enforced, users without two-factor are sent to set it up', function () {
    config(['auth.two_factor.enforced' => true]);

    $this->actingAs($this->user)->get('/dashboard')->assertRedirect(route('profile.edit').'#two-factor');
    $this->actingAs($this->user)->get('/patients')->assertRedirect(route('profile.edit').'#two-factor');
    $this->actingAs($this->user)->getJson('/doctors')->assertForbidden();
    $this->actingAs($this->user)->get('/profile')->assertOk();

    app(TwoFactor::class)->enable($this->user);
    $this->user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->actingAs($this->user->fresh())->get('/patients')->assertRedirect(route('dashboard', ['tab' => 'patients']));
});
