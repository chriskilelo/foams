<?php

namespace App\Http\Controllers\Issues;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\ReporterCategory;
use App\Enums\ResolutionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issues\ResolveIssueRequest;
use App\Http\Requests\Issues\StoreIssueRequest;
use App\Http\Requests\Issues\UpdateIssueStatusRequest;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\County;
use App\Models\Issue;
use App\Services\IssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    public function __construct(private IssueService $issueService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Issue::class);

        $issues = Issue::query()
            ->with(['county.region', 'createdBy:id,name', 'assignedTo:id,name'])
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->input('severity')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('county_id'), fn ($q) => $q->where('county_id', $request->integer('county_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('reference_number', 'like', '%'.$request->input('search').'%')
                ->orWhere('issue_type', 'like', '%'.$request->input('search').'%')
                ->orWhere('description', 'like', '%'.$request->input('search').'%')
            ))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $user = $request->user();

        $counties = County::query()
            ->when(
                $user->hasAnyRole(['ricto', 'icto', 'aicto']) && $user->region_id,
                fn ($q) => $q->where('region_id', $user->region_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $severities = collect(IssueSeverity::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ]);

        $statuses = collect(IssueStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucwords(str_replace('_', ' ', $s->value)),
        ]);

        return Inertia::render('Issues/Index', [
            'issues' => $issues,
            'counties' => $counties,
            'severities' => $severities,
            'statuses' => $statuses,
            'filters' => $request->only(['severity', 'status', 'county_id', 'search']),
        ]);
    }

    public function show(Issue $issue): Response
    {
        $this->authorize('view', $issue);

        $issue->load([
            'asset:id,asset_code,name,type',
            'county.region',
            'createdBy:id,name',
            'assignedTo:id,name',
            'resolution.resolvedBy:id,name',
        ]);

        $activities = Inertia::defer(fn () => $issue->activities()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get());

        $attachments = Inertia::defer(fn () => $issue->attachments()
            ->with('uploadedBy:id,name')
            ->get());

        $resolutionTypes = collect(ResolutionType::cases())->map(fn ($r) => [
            'value' => $r->value,
            'label' => ucfirst($r->value),
        ]);

        $allowedTransitions = $this->allowedTransitions($issue);

        return Inertia::render('Issues/Show', [
            'issue' => $issue,
            'activities' => $activities,
            'attachments' => $attachments,
            'resolution_types' => $resolutionTypes,
            'allowed_transitions' => $allowedTransitions,
            'can' => [
                'update_status' => auth()->user()->can('updateStatus', $issue),
                'escalate' => auth()->user()->can('escalate', $issue),
                'resolve' => auth()->user()->can('resolve', $issue),
                'close' => auth()->user()->can('close', $issue),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Issue::class);

        $user = $request->user();

        $counties = County::query()
            ->when(
                $user->hasAnyRole(['ricto', 'icto', 'aicto']) && $user->region_id,
                fn ($q) => $q->where('region_id', $user->region_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $assets = Asset::query()
            ->orderBy('asset_code')
            ->get(['id', 'asset_code', 'name', 'county_id']);

        $severities = collect(IssueSeverity::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ]);

        $reporterCategories = collect(ReporterCategory::cases())->map(fn ($r) => [
            'value' => $r->value,
            'label' => ucwords(str_replace('_', ' ', $r->value)),
        ]);

        return Inertia::render('Issues/Create', [
            'counties' => $counties,
            'assets' => $assets,
            'severities' => $severities,
            'reporter_categories' => $reporterCategories,
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $this->authorize('create', Issue::class);

        $issue = $this->issueService->createIssue(
            $request->validated(),
            $request->user()
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'issue.created',
            'auditable_type' => Issue::class,
            'auditable_id' => $issue->id,
            'old_values' => [],
            'new_values' => ['reference_number' => $issue->reference_number, 'severity' => $issue->severity->value],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('issues.show', $issue)
            ->with('success', "Issue {$issue->reference_number} created.");
    }

    public function updateStatus(UpdateIssueStatusRequest $request, Issue $issue): RedirectResponse
    {
        $this->authorize('updateStatus', $issue);

        $previousStatus = $issue->status->value;

        $this->issueService->transitionStatus(
            $issue,
            $request->validated()['status'],
            $request->user(),
            $request->validated()['comment'] ?? null
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'issue.status_changed',
            'auditable_type' => Issue::class,
            'auditable_id' => $issue->id,
            'old_values' => ['status' => $previousStatus],
            'new_values' => ['status' => $request->validated()['status']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('issues.show', $issue)
            ->with('success', 'Issue status updated.');
    }

    public function escalate(Request $request, Issue $issue): RedirectResponse
    {
        $this->authorize('escalate', $issue);

        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->issueService->escalate($issue, $request->user(), $request->input('reason'));

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'issue.escalated',
            'auditable_type' => Issue::class,
            'auditable_id' => $issue->id,
            'old_values' => ['is_escalated' => false],
            'new_values' => ['is_escalated' => true, 'reason' => $request->input('reason')],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('issues.show', $issue)
            ->with('success', 'Issue escalated.');
    }

    public function resolve(ResolveIssueRequest $request, Issue $issue): RedirectResponse
    {
        $this->authorize('resolve', $issue);

        $this->issueService->resolve($issue, $request->user(), $request->validated());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'issue.resolved',
            'auditable_type' => Issue::class,
            'auditable_id' => $issue->id,
            'old_values' => ['status' => 'in_progress'],
            'new_values' => ['status' => 'resolved'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('issues.show', $issue)
            ->with('success', 'Issue resolved.');
    }

    public function close(Request $request, Issue $issue): RedirectResponse
    {
        $this->authorize('close', $issue);

        $this->issueService->close($issue, $request->user());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'issue.closed',
            'auditable_type' => Issue::class,
            'auditable_id' => $issue->id,
            'old_values' => ['status' => 'resolved'],
            'new_values' => ['status' => 'closed'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('issues.show', $issue)
            ->with('success', 'Issue closed.');
    }

    /**
     * Return the allowed next statuses given the issue's current status,
     * excluding the escalated transition (which has its own dedicated endpoint).
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function allowedTransitions(Issue $issue): array
    {
        $currentStatus = $issue->status instanceof IssueStatus
            ? $issue->status->value
            : (string) $issue->status;

        $allowed = match ($currentStatus) {
            'new' => ['acknowledged', 'duplicate'],
            'acknowledged' => ['in_progress', 'duplicate'],
            'in_progress' => ['pending_third_party'],
            'pending_third_party' => ['in_progress'],
            'escalated' => ['in_progress'],
            default => [],
        };

        return collect($allowed)->map(fn ($s) => [
            'value' => $s,
            'label' => ucwords(str_replace('_', ' ', $s)),
        ])->values()->all();
    }
}
