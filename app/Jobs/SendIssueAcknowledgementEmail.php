<?php

namespace App\Jobs;

use App\Enums\ReporterCategory;
use App\Mail\IssueAcknowledgedMail;
use App\Models\Issue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendIssueAcknowledgementEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $issue = $this->issue->fresh();

        if (! $issue) {
            return;
        }

        $category = $issue->reporter_category instanceof ReporterCategory
            ? $issue->reporter_category->value
            : (string) $issue->reporter_category;

        if (! in_array($category, ['general_public', 'public_servant'], true)) {
            return;
        }

        if (! $issue->reporter_email) {
            return;
        }

        Mail::to($issue->reporter_email)->send(new IssueAcknowledgedMail($issue));
    }
}
