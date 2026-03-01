<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard passes stat props to the view', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('stats.assets_total')
            ->has('stats.assets_online')
            ->has('stats.assets_degraded')
            ->has('stats.assets_down')
            ->has('stats.avg_uptime_30d')
            ->has('stats.open_issues')
            ->has('stats.logs_today')
        );
});
