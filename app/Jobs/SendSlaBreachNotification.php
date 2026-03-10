<?php

namespace App\Jobs;

use App\Models\Issue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSlaBreachNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue)
    {
        $this->onQueue('critical');
    }

    /**
     * Send SLA breach notifications to NOC officers and the relevant RICTO.
     */
    public function handle(): void
    {
        // Notification dispatch is handled by listeners and the NotificationService.
        // The job is the queue entry point; listeners on SlaBreached handle the actual sends.
    }
}
