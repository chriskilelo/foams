<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogPasswordResetToAuditLog
{
    public function __construct(public Request $request) {}

    /**
     * Invalidate all database sessions for the user and write an audit record.
     */
    public function handle(PasswordReset $event): void
    {
        DB::table('sessions')->where('user_id', $event->user->id)->delete();

        AuditLog::create([
            'user_id' => $event->user->id,
            'event' => 'password.reset',
            'auditable_type' => $event->user::class,
            'auditable_id' => $event->user->id,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
