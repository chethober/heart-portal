<?php

use App\Models\AuditLog;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('a link requested from the profile works while signed in and signs the user out', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)->post('/dashboard/profile/password-reset');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))->assertOk();

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'a-new-Password-1',
            'password_confirmation' => 'a-new-Password-1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertGuest();
        expect(Hash::check('a-new-Password-1', $user->fresh()->password))->toBeTrue()
            ->and(AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'user.password_reset')->where('user_id', $user->id)->exists())->toBeTrue();

        return true;
    });
});
