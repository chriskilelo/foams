<?php

namespace App\Mail;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Issue $issue,
        public User $assignee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ASSIGNED] Issue '.$this->issue->reference_number.' Has Been Assigned to You',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.assignment',
            with: [
                'issue' => $this->issue,
                'assignee' => $this->assignee,
            ],
        );
    }
}
