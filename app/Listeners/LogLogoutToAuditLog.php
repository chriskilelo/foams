<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogLogoutToAuditLog
{
    public function __construct(public Request $request) {}

    /**
     * Write an immutable audit record when a user logs out.
     */
    public function handle(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->id,
            'event' => 'logout',
            'auditable_type' => $event->user::class,
            'auditable_id' => $event->user->id,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
