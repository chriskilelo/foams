<?php

use App\Models\AuditLog;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ──────────────────────────────────────────────────────────────────

function adminForUsers(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function rictoForUsers(): User
{
    $user = User::factory()->create();
    $user->assignRole('ricto');

    return $user;
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('admin users index', function () {
    it('admin can view the users index', function () {
        $this->actingAs(adminForUsers())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));
    });

    it('non-admin gets 403 on users index', function () {
        $this->actingAs(rictoForUsers())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login from users index', function () {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Create ───────────────────────────────────────────────────────────────────

describe('admin users create', function () {
    it('admin can view the create form', function () {
        $this->actingAs(adminForUsers())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Users/Create'));
    });

    it('non-admin gets 403 on create form', function () {
        $this->actingAs(rictoForUsers())
            ->get(route('admin.users.create'))
            ->assertForbidden();
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('admin users store', function () {
    it('admin can create a user', function () {
        $region = Region::factory()->create();

        $this->actingAs(adminForUsers())
            ->post(route('admin.users.store'), [
                'name' => 'Test ICTO',
                'username' => 'test_icto_user',
                'email' => 'icto@test.go.ke',
                'phone' => '0712345678',
                'password' => 'Password123!',
                'role' => 'icto',
                'region_id' => $region->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'icto@test.go.ke')->firstOrFail();
        expect($user->name)->toBe('Test ICTO');
        expect($user->hasRole('icto'))->toBeTrue();
        expect($user->region_id)->toBe($region->id);
        expect($user->is_active)->toBeTrue();
    });

    it('store logs the creation to audit_logs', function () {
        $admin = adminForUsers();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Audit User',
                'username' => 'audit_user_xyz',
                'email' => 'audit@test.go.ke',
                'password' => 'Password123!',
                'role' => 'noc',
                'region_id' => null,
            ]);

        expect(
            AuditLog::query()
                ->where('user_id', $admin->id)
                ->where('event', 'user.created')
                ->exists()
        )->toBeTrue();
    });

    it('store fails validation on missing required fields', function () {
        $this->actingAs(adminForUsers())
            ->post(route('admin.users.store'), [])
            ->assertSessionHasErrors(['name', 'username', 'email', 'password', 'role']);
    });

    it('store fails validation on duplicate email', function () {
        User::factory()->create(['email' => 'taken@test.go.ke']);

        $this->actingAs(adminForUsers())
            ->post(route('admin.users.store'), [
                'name' => 'Another',
                'username' => 'another_user_xyz',
                'email' => 'taken@test.go.ke',
                'password' => 'Password123!',
                'role' => 'noc',
            ])
            ->assertSessionHasErrors('email');
    });

    it('store fails validation on duplicate username', function () {
        User::factory()->create(['username' => 'taken_username']);

        $this->actingAs(adminForUsers())
            ->post(route('admin.users.store'), [
                'name' => 'Another',
                'username' => 'taken_username',
                'email' => 'unique@test.go.ke',
                'password' => 'Password123!',
                'role' => 'noc',
            ])
            ->assertSessionHasErrors('username');
    });

    it('store fails validation on invalid role', function () {
        $this->actingAs(adminForUsers())
            ->post(route('admin.users.store'), [
                'name' => 'Bad Role',
                'username' => 'bad_role_user',
                'email' => 'bad@test.go.ke',
                'password' => 'Password123!',
                'role' => 'supervillain',
            ])
            ->assertSessionHasErrors('role');
    });

    it('non-admin gets 403 on store', function () {
        $this->actingAs(rictoForUsers())
            ->post(route('admin.users.store'), [
                'name' => 'Hack',
                'username' => 'hacker_user',
                'email' => 'hack@test.go.ke',
                'password' => 'Password123!',
                'role' => 'admin',
            ])
            ->assertForbidden();
    });
});

// ─── Edit / Update ────────────────────────────────────────────────────────────

describe('admin users edit and update', function () {
    it('admin can view the edit form', function () {
        $user = User::factory()->create();
        $user->assignRole('icto');

        $this->actingAs(adminForUsers())
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Edit')
                ->where('user.id', $user->id)
            );
    });

    it('non-admin gets 403 on edit form', function () {
        $user = User::factory()->create();

        $this->actingAs(rictoForUsers())
            ->get(route('admin.users.edit', $user))
            ->assertForbidden();
    });

    it('admin can update a user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('icto');
        $region = Region::factory()->create();

        $this->actingAs(adminForUsers())
            ->put(route('admin.users.update', $user), [
                'name' => 'New Name',
                'username' => $user->username,
                'email' => $user->email,
                'phone' => null,
                'role' => 'aicto',
                'region_id' => $region->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        expect($user->name)->toBe('New Name');
        expect($user->hasRole('aicto'))->toBeTrue();
        expect($user->hasRole('icto'))->toBeFalse();
    });

    it('update allows keeping same email and username for same user', function () {
        $user = User::factory()->create(['name' => 'Same User']);
        $user->assignRole('noc');

        $this->actingAs(adminForUsers())
            ->put(route('admin.users.update', $user), [
                'name' => 'Same User',
                'username' => $user->username,
                'email' => $user->email,
                'phone' => null,
                'role' => 'noc',
                'region_id' => null,
            ])
            ->assertRedirect(route('admin.users.index'));
    });

    it('update fails validation on duplicate email', function () {
        $existing = User::factory()->create(['email' => 'other@test.go.ke']);
        $user = User::factory()->create();
        $user->assignRole('noc');

        $this->actingAs(adminForUsers())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => 'other@test.go.ke',
                'role' => 'noc',
                'region_id' => null,
            ])
            ->assertSessionHasErrors('email');
    });
});

// ─── Deactivate ───────────────────────────────────────────────────────────────

describe('admin users deactivate', function () {
    it('admin can deactivate a user', function () {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('icto');

        $this->actingAs(adminForUsers())
            ->patch(route('admin.users.deactivate', $user))
            ->assertRedirect(route('admin.users.index'));

        expect($user->fresh()->is_active)->toBeFalse();
    });

    it('deactivation deletes the user sessions', function () {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('icto');

        // Simulate a database session for this user
        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testing',
            'payload' => 'fake-payload',
            'last_activity' => time(),
        ]);

        expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(1);

        $this->actingAs(adminForUsers())
            ->patch(route('admin.users.deactivate', $user));

        expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    });

    it('deactivation is logged to audit_logs', function () {
        $admin = adminForUsers();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('icto');

        $this->actingAs($admin)
            ->patch(route('admin.users.deactivate', $user));

        expect(
            AuditLog::query()
                ->where('user_id', $admin->id)
                ->where('event', 'user.deactivated')
                ->where('auditable_id', $user->id)
                ->exists()
        )->toBeTrue();
    });

    it('non-admin gets 403 on deactivate', function () {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs(rictoForUsers())
            ->patch(route('admin.users.deactivate', $user))
            ->assertForbidden();
    });
});

// ─── Deactivated user login ───────────────────────────────────────────────────

describe('deactivated user cannot log in', function () {
    it('deactivated user is rejected at login', function () {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('active user can still log in normally', function () {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    });
});
