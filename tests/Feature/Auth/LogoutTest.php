<?php

use App\Models\AuditLog;
use App\Models\User;

test('logout clears the session', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    $this->post(route('logout'));

    $this->assertGuest();
});

test('logout clears the auth.two_factor_confirmed session key', function () {
    $user = User::factory()->create();

    // Establish a session with the 2FA-confirmed key present, then log out
    $this->actingAs($user)
        ->withSession(['auth.two_factor_confirmed' => true])
        ->post(route('logout'));

    $this->assertGuest();

    // Session is invalidated on logout; the 2FA key must not persist
    expect(session('auth.two_factor_confirmed'))->toBeFalsy();
});

test('logout is logged to audit_logs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    expect(
        AuditLog::query()
            ->where('user_id', $user->id)
            ->where('event', 'logout')
            ->exists()
    )->toBeTrue();
});
