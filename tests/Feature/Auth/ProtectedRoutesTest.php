<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

// ─── Unauthenticated redirect to /login ────────────────────────────────────

test('unauthenticated users are redirected to login from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('unauthenticated users are redirected to login from settings profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('unauthenticated users are redirected to login from settings password', function () {
    $this->get(route('user-password.edit'))->assertRedirect(route('login'));
});

test('unauthenticated users are redirected to login from settings appearance', function () {
    $this->get(route('appearance.edit'))->assertRedirect(route('login'));
});

test('unauthenticated users are redirected to login from two-factor settings', function () {
    $this->get(route('two-factor.show'))->assertRedirect(route('login'));
});

// ─── Public role cannot access authenticated routes ────────────────────────

describe('users with the public role', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    test('are redirected away from the dashboard', function () {
        $user = User::factory()->create();
        $user->assignRole('public');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    });

    test('are redirected away from settings routes', function () {
        $user = User::factory()->create();
        $user->assignRole('public');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    });

    test('are logged out when accessing an authenticated route', function () {
        $user = User::factory()->create();
        $user->assignRole('public');

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertGuest();
    });
});
