<?php

namespace App\Mail;

use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Issue $issue,
        public IssueStatus $previousStatus,
        public IssueStatus $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Issue '.$this->issue->reference_number.' Status Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.issue-status-changed',
            with: [
                'issue' => $this->issue,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
            ],
        );
    }
}
