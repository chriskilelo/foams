<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const AVAILABLE_ROLES = ['admin', 'director', 'noc', 'ricto', 'icto', 'aicto', 'public_servant'];

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles', 'region:id,name'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        $regions = Region::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Users/Create', [
            'regions' => $regions,
            'roles' => self::AVAILABLE_ROLES,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data + ['is_active' => true]);
        $user->assignRole($role);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'user.created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => ['name' => $user->name, 'role' => $role, 'email' => $user->email],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load(['roles', 'region:id,name']);
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'regions' => $regions,
            'roles' => self::AVAILABLE_ROLES,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $user->update($data);
        $user->syncRoles([$role]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'user.updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => ['name' => $user->name, 'role' => $role],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.users.index');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $user->update(['is_active' => false]);

        // Invalidate all active sessions for this user via the database session driver.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'user.deactivated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => ['is_active' => true],
            'new_values' => ['is_active' => false],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.users.index');
    }
}
