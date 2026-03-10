<?php

namespace App\Mail;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Issue $issue,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ESCALATION] Issue '.$this->issue->reference_number.' Requires Director Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.escalation',
            with: [
                'issue' => $this->issue,
                'reason' => $this->reason,
            ],
        );
    }
}
