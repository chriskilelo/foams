<?php

namespace App\Http\Controllers\Issues;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\County;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegionalPanelController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewRegionalPanel', Issue::class);

        $user = $request->user();
        $regionId = $user->region_id;
        $today = today()->toDateString();
        $openStatuses = ['new', 'acknowledged', 'in_progress', 'pending_third_party', 'escalated'];

        $stats = [
            'critical_open' => Issue::query()
                ->where('severity', IssueSeverity::Critical)
                ->whereIn('status', $openStatuses)
                ->count(),
            'high_open' => Issue::query()
                ->where('severity', IssueSeverity::High)
                ->whereIn('status', $openStatuses)
                ->count(),
            'medium_open' => Issue::query()
                ->where('severity', IssueSeverity::Medium)
                ->whereIn('status', $openStatuses)
                ->count(),
            'resolved_today' => Issue::query()
                ->where('status', IssueStatus::Resolved)
                ->whereDate('resolved_at', $today)
                ->count(),
        ];

        $issues = Issue::query()
            ->with(['county', 'assignedTo:id,name'])
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->input('severity')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('county_id'), fn ($q) => $q->where('county_id', $request->integer('county_id')))
            ->when($request->filled('issue_type'), fn ($q) => $q->where('issue_type', $request->input('issue_type')))
            ->when($request->boolean('sla_breached'), fn ($q) => $q->where('sla_breached', true))
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        // Officer compliance: ICTO/AICTO users in this region
        $officers = User::query()
            ->where('region_id', $regionId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['icto', 'aicto']))
            ->where('is_active', true)
            ->get(['id', 'name']);

        $assetCount = Asset::withoutGlobalScopes()
            ->whereHas('county', fn ($q) => $q->where('region_id', $regionId))
            ->count();

        $loggedTodayByOfficer = AssetStatusLog::withoutGlobalScopes()
            ->whereDate('logged_date', $today)
            ->whereIn('user_id', $officers->pluck('id'))
            ->selectRaw('user_id, COUNT(DISTINCT asset_id) as logged_count')
            ->groupBy('user_id')
            ->pluck('logged_count', 'user_id');

        $compliance = $officers->map(fn (User $officer) => [
            'id' => $officer->id,
            'name' => $officer->name,
            'logged_today' => (int) ($loggedTodayByOfficer[$officer->id] ?? 0),
            'total_assets' => $assetCount,
        ]);

        $counties = County::query()
            ->when($regionId, fn ($q) => $q->where('region_id', $regionId))
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

        return Inertia::render('Issues/RegionalPanel', [
            'stats' => $stats,
            'issues' => $issues,
            'compliance' => $compliance,
            'counties' => $counties,
            'severities' => $severities,
            'statuses' => $statuses,
            'filters' => $request->only(['severity', 'status', 'county_id', 'issue_type', 'sla_breached']),
        ]);
    }
}
