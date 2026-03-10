<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyStatusReminder;
use App\Models\User;
use Illuminate\Console\Command;

class SendStatusRemindersCommand extends Command
{
    protected $signature = 'foams:send-status-reminders
                            {--include-ricto : Also notify RICTO for regions where officers have not logged}';

    protected $description = 'Dispatch daily status log reminders to field officers (and optionally their RICTO)';

    public function handle(): int
    {
        $officersWithoutLogs = User::role(['icto', 'aicto'])
            ->where('is_active', true)
            ->whereNotNull('region_id')
            ->whereNotNull('email')
            ->whereDoesntHave('assetStatusLogs', fn ($q) => $q->whereDate('logged_date', today()))
            ->get();

        foreach ($officersWithoutLogs as $officer) {
            SendDailyStatusReminder::dispatch($officer);
        }

        $this->info("Dispatched {$officersWithoutLogs->count()} officer reminder(s).");

        if ($this->option('include-ricto')) {
            $regionIds = $officersWithoutLogs->pluck('region_id')->unique()->filter();

            $rictos = User::role('ricto')
                ->where('is_active', true)
                ->whereIn('region_id', $regionIds)
                ->whereNotNull('email')
                ->get();

            foreach ($rictos as $ricto) {
                SendDailyStatusReminder::dispatch($ricto, true);
            }

            $this->info("Dispatched {$rictos->count()} RICTO summary reminder(s).");
        }

        return self::SUCCESS;
    }
}
