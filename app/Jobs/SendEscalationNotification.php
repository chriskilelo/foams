<?php

namespace App\Jobs;

use App\Mail\EscalationMail;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEscalationNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue, public string $reason)
    {
        $this->onQueue('critical');
    }

    public function handle(): void
    {
        $issue = $this->issue->fresh(['county', 'escalatedBy']);

        if (! $issue) {
            return;
        }

        User::role('director')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->each(function (User $director) use ($issue): void {
                Mail::to($director->email)->send(new EscalationMail($issue, $this->reason));
            });
    }
}
