<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Events\IssueEscalated;
use App\Events\IssueRaised;
use App\Events\IssueStatusChanged;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Resolution;
use App\Models\SlaConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IssueService
{
    /**
     * Allowed status transitions: from → [allowed next statuses]
     *
     * @var array<string, string[]>
     */
    private const TRANSITIONS = [
        'new' => ['acknowledged', 'duplicate'],
        'acknowledged' => ['in_progress', 'duplicate'],
        'in_progress' => ['pending_third_party', 'escalated', 'resolved'],
        'pending_third_party' => ['in_progress', 'resolved'],
        'escalated' => ['in_progress', 'resolved'],
        'resolved' => ['closed'],
        'closed' => [],
        'duplicate' => [],
    ];

    /**
     * Create a new issue, generating its reference number and computing sla_due_at
     * from the active SLA configuration for the issue's severity.
     */
    public function createIssue(array $data, ?User $creator): Issue
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['reference_number'] = $this->generateReferenceNumber();
            $data['status'] = IssueStatus::New->value;

            if ($creator) {
                $data['created_by_user_id'] = $creator->id;
            }

            $severity = $data['severity'] ?? null;
            $data['sla_due_at'] = $this->computeSlaDeadline($severity);

            $issue = Issue::create($data);

            $issue->load('county');

            IssueRaised::dispatch($issue);

            return $issue;
        });
    }

    /**
     * Transition an issue to a new status, recording the activity and firing an event.
     *
     * @throws InvalidArgumentException if the transition is not allowed.
     */
    public function transitionStatus(Issue $issue, string $newStatus, User $actor, ?string $comment = null): void
    {
        $currentStatus = $issue->status instanceof IssueStatus
            ? $issue->status->value
            : (string) $issue->status;

        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                "Cannot transition issue from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $previousStatus = $issue->status instanceof IssueStatus
            ? $issue->status
            : IssueStatus::from($currentStatus);

        DB::transaction(function () use ($issue, $newStatus, $actor, $comment, $previousStatus) {
            $timestamps = match ($newStatus) {
                'acknowledged' => ['acknowledged_at' => now()],
                'resolved' => ['resolved_at' => now()],
                'closed' => ['closed_at' => now()],
                default => [],
            };

            $issue->update(array_merge(['status' => $newStatus], $timestamps));

            IssueActivity::create([
                'issue_id' => $issue->id,
                'user_id' => $actor->id,
                'action_type' => 'status_change',
                'previous_status' => $previousStatus->value,
                'new_status' => $newStatus,
                'comment' => $comment,
                'is_internal' => false,
            ]);

            $newStatusEnum = IssueStatus::from($newStatus);
            $issue->load('county');

            IssueStatusChanged::dispatch($issue, $previousStatus, $newStatusEnum, $actor);
        });
    }

    /**
     * Escalate an issue, setting escalation fields and creating an activity record.
     */
    public function escalate(Issue $issue, User $actor, string $reason): void
    {
        DB::transaction(function () use ($issue, $actor, $reason) {
            $previousStatus = $issue->status instanceof IssueStatus
                ? $issue->status
                : IssueStatus::from((string) $issue->status);

            $issue->update([
                'is_escalated' => true,
                'escalated_at' => now(),
                'escalated_by_user_id' => $actor->id,
                'status' => IssueStatus::Escalated->value,
            ]);

            IssueActivity::create([
                'issue_id' => $issue->id,
                'user_id' => $actor->id,
                'action_type' => 'escalation',
                'previous_status' => $previousStatus->value,
                'new_status' => IssueStatus::Escalated->value,
                'comment' => $reason,
                'is_internal' => false,
            ]);

            $issue->load('county');

            IssueEscalated::dispatch($issue, $actor, $reason);
        });
    }

    /**
     * Resolve an issue, creating a Resolution record and transitioning status to 'resolved'.
     *
     * @param  array{root_cause: string, steps_taken?: array<int, string>, resolution_type: string}  $resolutionData
     */
    public function resolve(Issue $issue, User $actor, array $resolutionData): void
    {
        DB::transaction(function () use ($issue, $actor, $resolutionData) {
            $previousStatus = $issue->status instanceof IssueStatus
                ? $issue->status
                : IssueStatus::from((string) $issue->status);

            Resolution::create([
                'issue_id' => $issue->id,
                'root_cause' => $resolutionData['root_cause'],
                'steps_taken' => $resolutionData['steps_taken'] ?? [],
                'resolution_type' => $resolutionData['resolution_type'],
                'resolved_by_user_id' => $actor->id,
                'resolved_at' => now(),
            ]);

            $issue->update([
                'status' => IssueStatus::Resolved->value,
                'resolved_at' => now(),
            ]);

            IssueActivity::create([
                'issue_id' => $issue->id,
                'user_id' => $actor->id,
                'action_type' => 'status_change',
                'previous_status' => $previousStatus->value,
                'new_status' => IssueStatus::Resolved->value,
                'comment' => 'Issue resolved: '.$resolutionData['root_cause'],
                'is_internal' => false,
            ]);

            $issue->load('county');

            IssueStatusChanged::dispatch($issue, $previousStatus, IssueStatus::Resolved, $actor);
        });
    }

    /**
     * Close a resolved issue.
     */
    public function close(Issue $issue, User $actor): void
    {
        $this->transitionStatus($issue, IssueStatus::Closed->value, $actor);

        $issue->update(['closed_at' => now()]);
    }

    /**
     * Generate the next sequential reference number in the format ISS-XXXX.
     */
    public function generateReferenceNumber(): string
    {
        $max = (int) Issue::withoutGlobalScopes()->max(
            DB::raw('CAST(SUBSTRING(reference_number, 5) AS UNSIGNED)')
        );

        return 'ISS-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Compute the SLA resolution deadline for a given severity.
     * Reads the most recent effective SLA configuration for the severity.
     */
    private function computeSlaDeadline(?string $severity): ?\Carbon\CarbonInterface
    {
        if (! $severity) {
            return null;
        }

        $config = SlaConfiguration::query()
            ->where('severity', $severity)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->first();

        if (! $config) {
            return null;
        }

        return now()->addHours($config->resolve_within_hrs);
    }
}
