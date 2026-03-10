<?php

namespace App\Jobs;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    /** Retry 3 times with exponential backoff (60s, 300s, 900s). */
    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Issue $issue,
        public User $ricto,
    ) {
        $this->onQueue('critical');
    }

    public function handle(): void
    {
        $phone = $this->ricto->phone;

        if (! $phone) {
            return;
        }

        $county = $this->issue->county?->name ?? 'Unknown County';
        $message = sprintf(
            'FOAMS ALERT: Critical issue %s reported in %s. Type: %s. Immediate attention required. Ref: %s',
            $this->issue->reference_number,
            $county,
            ucwords(str_replace('_', ' ', $this->issue->issue_type)),
            $this->issue->reference_number,
        );

        if (app()->environment('local', 'testing')) {
            Log::info('SMS [local]: to='.$phone.' | message='.$message);

            return;
        }

        try {
            $AT = new AfricasTalking(
                config('services.africastalking.username'),
                config('services.africastalking.api_key'),
            );

            $AT->sms()->send([
                'to' => $phone,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            AuditLog::create([
                'event' => 'sms_send_failed',
                'auditable_type' => Issue::class,
                'auditable_id' => $this->issue->id,
                'new_values' => [
                    'error' => $e->getMessage(),
                    'phone' => $phone,
                    'attempt' => $this->attempts(),
                ],
            ]);

            throw $e;
        }
    }
}
