<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class CheckSlaCommand extends Command
{
    protected $signature = 'foams:check-sla';

    protected $description = 'Flag SLA-breached issues and dispatch breach/warning notifications';

    public function handle(SlaService $slaService): int
    {
        $slaService->runSlaCheck();

        $this->info('SLA check complete.');

        return self::SUCCESS;
    }
}
