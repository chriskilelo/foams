<?php

namespace App\Mail;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaBreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Issue $issue) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SLA BREACH] Issue '.$this->issue->reference_number.' – Immediate Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sla-breach',
            with: ['issue' => $this->issue],
        );
    }
}
