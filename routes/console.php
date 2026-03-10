<?php

use App\Console\Commands\CheckSlaCommand;
use App\Console\Commands\SendStatusRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckSlaCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 16:00 EAT (UTC+3) = 13:00 UTC — remind officers who have not logged today
Schedule::command(SendStatusRemindersCommand::class)
    ->dailyAt('13:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->runInBackground();

// 18:00 EAT (UTC+3) = 15:00 UTC — repeat officer reminder + notify RICTO of non-compliant officers
Schedule::command(SendStatusRemindersCommand::class, ['--include-ricto'])
    ->dailyAt('18:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->runInBackground();
