<?php

namespace App\Jobs;

use App\Mail\SlaBreachMail;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSlaBreachNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue)
    {
        $this->onQueue('critical');
    }

    /**
     * Send SLA breach notifications to all NOC officers and the RICTO for the issue's region.
     */
    public function handle(): void
    {
        $issue = $this->issue->fresh(['county.region']);

        if (! $issue) {
            return;
        }

        $region = $issue->county?->region;

        $nocUsers = User::role('noc')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $rictoUsers = $region
            ? User::role('ricto')
                ->where('is_active', true)
                ->where('region_id', $region->id)
                ->whereNotNull('email')
                ->get()
            : collect();

        $nocUsers->merge($rictoUsers)->each(function (User $recipient) use ($issue): void {
            Mail::to($recipient->email)->send(new SlaBreachMail($issue));
        });
    }
}
