<?php

namespace App\Http\Controllers\Issues;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Issue;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NocPanelController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewNocPanel', Issue::class);

        $openStatuses = ['new', 'acknowledged', 'in_progress', 'pending_third_party', 'escalated'];

        $stats = [
            'critical_open' => Issue::withoutGlobalScopes()
                ->where('severity', IssueSeverity::Critical)
                ->whereIn('status', $openStatuses)
                ->count(),
            'high_open' => Issue::withoutGlobalScopes()
                ->where('severity', IssueSeverity::High)
                ->whereIn('status', $openStatuses)
                ->count(),
            'medium_open' => Issue::withoutGlobalScopes()
                ->where('severity', IssueSeverity::Medium)
                ->whereIn('status', $openStatuses)
                ->count(),
            'resolved_today' => Issue::withoutGlobalScopes()
                ->where('status', IssueStatus::Resolved)
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        $issues = Issue::withoutGlobalScopes()
            ->with(['county.region', 'assignedTo:id,name'])
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->input('severity')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('region_id'), fn ($q) => $q->whereHas('county', fn ($q) => $q->where('region_id', $request->integer('region_id'))))
            ->when($request->filled('county_id'), fn ($q) => $q->where('county_id', $request->integer('county_id')))
            ->when($request->filled('issue_type'), fn ($q) => $q->where('issue_type', $request->input('issue_type')))
            ->when($request->boolean('sla_breached'), fn ($q) => $q->where('sla_breached', true))
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $regions = Region::query()->orderBy('name')->get(['id', 'name']);
        $counties = County::query()->orderBy('name')->get(['id', 'name', 'region_id']);

        $severities = collect(IssueSeverity::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ]);

        $statuses = collect(IssueStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucwords(str_replace('_', ' ', $s->value)),
        ]);

        return Inertia::render('Issues/NocPanel', [
            'stats' => $stats,
            'issues' => $issues,
            'regions' => $regions,
            'counties' => $counties,
            'severities' => $severities,
            'statuses' => $statuses,
            'filters' => $request->only(['severity', 'status', 'region_id', 'county_id', 'issue_type', 'sla_breached']),
        ]);
    }
}
