<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IssueSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSlaConfigurationRequest;
use App\Http\Requests\Admin\UpdateSlaConfigurationRequest;
use App\Models\AuditLog;
use App\Models\SlaConfiguration;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SlaConfigurationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SlaConfiguration::class);

        $configurations = SlaConfiguration::query()
            ->with('createdBy:id,name')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('effective_from')
            ->get();

        return Inertia::render('Admin/SlaConfig/Index', [
            'configurations' => $configurations,
            'severities' => array_column(IssueSeverity::cases(), 'value'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SlaConfiguration::class);

        return Inertia::render('Admin/SlaConfig/Create', [
            'severities' => array_column(IssueSeverity::cases(), 'value'),
        ]);
    }

    public function store(StoreSlaConfigurationRequest $request): RedirectResponse
    {
        $config = SlaConfiguration::create(
            $request->validated() + ['created_by_user_id' => $request->user()->id]
        );

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'sla_configuration.created',
            'auditable_type' => SlaConfiguration::class,
            'auditable_id' => $config->id,
            'old_values' => null,
            'new_values' => $request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.sla-configurations.index');
    }

    public function edit(SlaConfiguration $slaConfiguration): Response
    {
        $this->authorize('update', $slaConfiguration);

        return Inertia::render('Admin/SlaConfig/Edit', [
            'configuration' => $slaConfiguration,
        ]);
    }

    public function update(UpdateSlaConfigurationRequest $request, SlaConfiguration $slaConfiguration): RedirectResponse
    {
        $old = $slaConfiguration->only(['acknowledge_within_hrs', 'resolve_within_hrs', 'effective_from']);

        $slaConfiguration->update($request->validated());

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'sla_configuration.updated',
            'auditable_type' => SlaConfiguration::class,
            'auditable_id' => $slaConfiguration->id,
            'old_values' => $old,
            'new_values' => $request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.sla-configurations.index');
    }
}
