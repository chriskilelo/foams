<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyStatusReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, User>  $officersWithoutLogs  Only populated for RICTO summary emails.
     */
    public function __construct(
        public User $user,
        public bool $isRictoSummary = false,
        public Collection $officersWithoutLogs = new Collection,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isRictoSummary
            ? 'Daily Status Log Summary – Officers Pending Submission'
            : 'Action Required: Submit Your Daily Asset Status Logs';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-status-reminder',
            with: [
                'user' => $this->user,
                'isRictoSummary' => $this->isRictoSummary,
                'officersWithoutLogs' => $this->officersWithoutLogs,
            ],
        );
    }
}
