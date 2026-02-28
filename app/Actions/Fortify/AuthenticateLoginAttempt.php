<?php

namespace App\Actions\Fortify;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateLoginAttempt
{
    public function __invoke(Request $request): ?User
    {
        $login = $request->input('email');

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user) {
            return null;
        }

        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            AuditLog::create([
                'user_id' => $user->id,
                'event' => 'login.blocked',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'reason' => 'account_locked',
                    'locked_until' => $user->locked_until->toIso8601String(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Your account has been locked due to too many failed login attempts. Please try again in 30 minutes.',
            ]);
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            $newAttempts = $user->failed_login_attempts + 1;
            $lockedUntil = $newAttempts >= 5 ? now()->addMinutes(30) : null;

            $user->update([
                'failed_login_attempts' => $newAttempts,
                'locked_until' => $lockedUntil,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'event' => 'login.failed',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => null,
                'new_values' => [
                    'reason' => 'invalid_password',
                    'failed_login_attempts' => $newAttempts,
                    'locked_until' => $lockedUntil?->toIso8601String(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return null;
        }

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'event' => 'login.success',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => ['ip_address' => $request->ip()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $user;
    }
}
