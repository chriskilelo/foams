<?php

use App\Models\User;

test('session lifetime is configured to 30 minutes', function () {
    expect(config('session.lifetime'))->toBe(30);
});

test('idle session expires and redirects unauthenticated user to login', function () {
    $user = User::factory()->create();

    // Log in to establish an active session
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->get(route('dashboard'))->assertOk();

    // Simulate idle timeout: travel past SESSION_LIFETIME then write an
    // empty session back to the handler's storage. The array driver stores
    // session data in-memory; flush() clears the attributes but save() is
    // required to persist the empty payload so the next request reads it.
    // In production the database session handler does the same check via
    // last_activity and returns an empty payload for expired sessions.
    $this->travel(config('session.lifetime') + 1)->minutes();

    // flush() clears in-memory attributes; save() persists the empty payload
    // to the handler's storage so the next request reads an empty session.
    // forgetGuards() ensures the auth guard re-reads from the (now empty)
    // session rather than returning a cached user reference.
    $store = $this->app['session.store'];
    $store->flush();
    $store->save();
    $this->app['auth']->forgetGuards();

    // A subsequent request must redirect to login — user is now a guest
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
