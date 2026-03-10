<?php

namespace App\Jobs;

use App\Enums\IssueStatus;
use App\Enums\ReporterCategory;
use App\Mail\IssueStatusChangedMail;
use App\Models\Issue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendStatusChangeEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Issue $issue,
        public IssueStatus $previousStatus,
        public IssueStatus $newStatus,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $issue = $this->issue->fresh();

        if (! $issue) {
            return;
        }

        if (! $issue->reporter_email) {
            return;
        }

        $category = $issue->reporter_category instanceof ReporterCategory
            ? $issue->reporter_category->value
            : (string) $issue->reporter_category;

        if ($category === ReporterCategory::FieldOfficer->value) {
            return;
        }

        Mail::to($issue->reporter_email)->send(
            new IssueStatusChangedMail($issue, $this->previousStatus, $this->newStatus)
        );
    }
}
