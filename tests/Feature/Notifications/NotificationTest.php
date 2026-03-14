<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function notifUser(string $role = 'noc'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function createDbNotification(User $user, bool $read = false): DatabaseNotification
{
    $notification = DatabaseNotification::create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['title' => 'Test notification', 'body' => 'This is a test.'],
        'read_at' => $read ? now() : null,
    ]);

    return $notification;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('notifications index', function () {
    it('authenticated user can view their notifications inbox', function () {
        $user = notifUser('noc');
        createDbNotification($user);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->has('notifications')
                ->has('unreadCount')
            );
    });

    it('guest is redirected to login', function () {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    });

    it('inbox only shows the authenticated user\'s own notifications', function () {
        $user = notifUser('noc');
        $other = notifUser('icto');
        createDbNotification($user);
        createDbNotification($other);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notifications.total', 1)
            );
    });

    it('unreadCount reflects only unread notifications', function () {
        $user = notifUser('ricto');
        createDbNotification($user, read: false);
        createDbNotification($user, read: true);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('unreadCount', 1)
            );
    });
});

// ─── Mark as read ─────────────────────────────────────────────────────────────

describe('notifications mark as read', function () {
    it('user can mark their own notification as read', function () {
        $user = notifUser('noc');
        $notification = createDbNotification($user);

        $this->actingAs($user)
            ->patch(route('notifications.mark-read', $notification->id))
            ->assertRedirect();

        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    it('user cannot mark another user\'s notification as read', function () {
        $user = notifUser('noc');
        $other = notifUser('icto');
        $notification = createDbNotification($other);

        $this->actingAs($user)
            ->patch(route('notifications.mark-read', $notification->id))
            ->assertForbidden();
    });

    it('guest is redirected when trying to mark as read', function () {
        $user = notifUser('noc');
        $notification = createDbNotification($user);

        $this->patch(route('notifications.mark-read', $notification->id))
            ->assertRedirect(route('login'));
    });
});

// ─── Mark all as read ─────────────────────────────────────────────────────────

describe('notifications mark all as read', function () {
    it('user can mark all their notifications as read', function () {
        $user = notifUser('ricto');
        createDbNotification($user, read: false);
        createDbNotification($user, read: false);

        $this->actingAs($user)
            ->post(route('notifications.mark-all-read'))
            ->assertRedirect();

        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('mark all as read does not affect other users', function () {
        $user = notifUser('noc');
        $other = notifUser('icto');
        createDbNotification($other, read: false);

        $this->actingAs($user)->post(route('notifications.mark-all-read'));

        expect($other->unreadNotifications()->count())->toBe(1);
    });
});

// ─── Destroy ─────────────────────────────────────────────────────────────────

describe('notifications destroy', function () {
    it('user can delete their own notification', function () {
        $user = notifUser('noc');
        $notification = createDbNotification($user);

        $this->actingAs($user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect();

        expect(DatabaseNotification::find($notification->id))->toBeNull();
    });

    it('user cannot delete another user\'s notification', function () {
        $user = notifUser('noc');
        $other = notifUser('icto');
        $notification = createDbNotification($other);

        $this->actingAs($user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertForbidden();

        expect(DatabaseNotification::find($notification->id))->not->toBeNull();
    });
});
