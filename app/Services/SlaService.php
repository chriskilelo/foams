<?php

namespace App\Services;

use App\Events\SlaBreached;
use App\Events\SlaNearingBreach;
use App\Jobs\SendSlaBreachNotification;
use App\Models\Issue;
use App\Models\SlaConfiguration;
use Carbon\Carbon;

class SlaService
{
    /**
     * Compute the SLA resolution deadline for an issue.
     *
     * Reads the most recent SLA configuration that was effective at the time
     * the issue was created, then adds resolve_within_hrs to created_at.
     */
    public function computeDueAt(Issue $issue): Carbon
    {
        $severity = $issue->severity->value;

        $config = SlaConfiguration::query()
            ->where('severity', $severity)
            ->where('effective_from', '<=', $issue->created_at)
            ->orderByDesc('effective_from')
            ->first();

        if (! $config) {
            return Carbon::parse($issue->created_at)->addHours(24);
        }

        return Carbon::parse($issue->created_at)->addHours($config->resolve_within_hrs);
    }

    /**
     * Run the SLA check across all open issues.
     *
     * - Issues where sla_due_at has passed and not yet flagged: mark breached,
     *   fire SlaBreached event, dispatch SendSlaBreachNotification job.
     * - Issues where >= 50% of the SLA window has elapsed (but not yet breached):
     *   fire SlaNearingBreach event.
     */
    public function runSlaCheck(): void
    {
        $openStatuses = ['new', 'acknowledged', 'in_progress', 'pending_third_party', 'escalated'];

        $this->flagBreachedIssues($openStatuses);
        $this->warnNearingBreachIssues($openStatuses);
    }

    /**
     * @param  string[]  $openStatuses
     */
    private function flagBreachedIssues(array $openStatuses): void
    {
        Issue::withoutGlobalScopes()
            ->whereIn('status', $openStatuses)
            ->where('sla_breached', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->with('county')
            ->each(function (Issue $issue): void {
                $issue->update(['sla_breached' => true]);

                SlaBreached::dispatch($issue);
                SendSlaBreachNotification::dispatch($issue);
            });
    }

    /**
     * Fire SlaNearingBreach for open issues that have consumed >= 50% of their
     * SLA window but have not yet breached.
     *
     * @param  string[]  $openStatuses
     */
    private function warnNearingBreachIssues(array $openStatuses): void
    {
        $nowTimestamp = now()->getTimestamp();

        Issue::withoutGlobalScopes()
            ->whereIn('status', $openStatuses)
            ->where('sla_breached', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '>', now())
            ->with('county')
            ->each(function (Issue $issue) use ($nowTimestamp): void {
                $created = $issue->created_at->getTimestamp();
                $due = $issue->sla_due_at->getTimestamp();
                $total = $due - $created;

                if ($total > 0 && ($nowTimestamp - $created) >= $total * 0.5) {
                    SlaNearingBreach::dispatch($issue);
                }
            });
    }
}
