<?php

namespace App\Mail;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueAcknowledgedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Issue $issue) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Issue Has Been Received – '.$this->issue->reference_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.issue-acknowledged',
            with: ['issue' => $this->issue],
        );
    }
}
