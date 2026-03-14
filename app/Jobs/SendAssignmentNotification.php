<?php

namespace App\Jobs;

use App\Mail\AssignmentMail;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAssignmentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Issue $issue,
        public User $assignee,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $issue = $this->issue->fresh(['county.region', 'asset', 'assignedTo']);

        if (! $issue || ! $this->assignee->email) {
            return;
        }

        Mail::to($this->assignee->email)->send(new AssignmentMail($issue, $this->assignee));
    }
}
