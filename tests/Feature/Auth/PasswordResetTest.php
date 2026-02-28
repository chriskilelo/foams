<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
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

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset token expires after 15 minutes', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        // Travel past the 15-minute token expiry window
        $this->travel(16)->minutes();

        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Expired token must be rejected
        $response->assertSessionHasErrors('email');

        return true;
    });
});

test('using a password reset token invalidates all sessions for the user', function () {
    Notification::fake();

    $user = User::factory()->create();

    // Seed existing sessions in the database as other active devices would
    DB::table('sessions')->insert([
        'id' => 'session-device-a',
        'user_id' => $user->id,
        'ip_address' => '1.2.3.4',
        'user_agent' => 'Mozilla/5.0',
        'payload' => base64_encode('session-data'),
        'last_activity' => now()->timestamp,
    ]);
    DB::table('sessions')->insert([
        'id' => 'session-device-b',
        'user_id' => $user->id,
        'ip_address' => '5.6.7.8',
        'user_agent' => 'curl/7.0',
        'payload' => base64_encode('session-data'),
        'last_activity' => now()->timestamp,
    ]);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(2);

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });

    // All sessions for this user must have been deleted
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

test('password reset is logged to audit_logs', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect(
        AuditLog::query()
            ->where('user_id', $user->id)
            ->where('event', 'password.reset')
            ->exists()
    )->toBeTrue();
});
