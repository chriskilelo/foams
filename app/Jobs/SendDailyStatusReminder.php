<?php

namespace App\Jobs;

use App\Mail\DailyStatusReminderMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDailyStatusReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public bool $isRictoSummary = false,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if (! $this->user->email) {
            return;
        }

        $officersWithoutLogs = collect();

        if ($this->isRictoSummary && $this->user->region_id) {
            $officersWithoutLogs = User::role(['icto', 'aicto'])
                ->where('is_active', true)
                ->where('region_id', $this->user->region_id)
                ->whereNotNull('email')
                ->whereDoesntHave('assetStatusLogs', fn ($q) => $q->whereDate('logged_date', today()))
                ->get();

            if ($officersWithoutLogs->isEmpty()) {
                return;
            }
        }

        Mail::to($this->user->email)->send(
            new DailyStatusReminderMail($this->user, $this->isRictoSummary, $officersWithoutLogs)
        );
    }
}
