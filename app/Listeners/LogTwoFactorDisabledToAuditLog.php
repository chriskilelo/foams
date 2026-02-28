<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class LogTwoFactorDisabledToAuditLog
{
    public function __construct(public Request $request) {}

    /**
     * Write an immutable audit record when a user disables two-factor authentication.
     */
    public function handle(TwoFactorAuthenticationDisabled $event): void
    {
        AuditLog::create([
            'user_id' => $event->user->id,
            'event' => 'two_factor_disabled',
            'auditable_type' => $event->user::class,
            'auditable_id' => $event->user->id,
            'old_values' => ['two_factor_enabled' => true],
            'new_values' => ['two_factor_enabled' => false],
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
