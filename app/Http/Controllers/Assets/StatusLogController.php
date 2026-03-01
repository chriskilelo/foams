<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetLogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreStatusLogRequest;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatusLogController extends Controller
{
    /**
     * Show the authenticated officer's daily log progress.
     *
     * Displays all assets assigned to them, indicating which have been logged
     * today (green tick) and which are still pending (amber).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AssetStatusLog::class);

        $user = $request->user();
        $today = now()->toDateString();

        $assets = Asset::query()
            ->where('assigned_to', $user->id)
            ->with('county')
            ->orderBy('asset_code')
            ->get();

        $loggedAssetIds = AssetStatusLog::query()
            ->where('user_id', $user->id)
            ->whereDate('logged_date', $today)
            ->pluck('asset_id')
            ->toArray();

        $assetList = $assets->map(fn (Asset $asset) => [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'type' => $asset->type->value,
            'status' => $asset->status->value,
            'location_name' => $asset->location_name,
            'county' => $asset->county->name,
            'logged_today' => in_array($asset->id, $loggedAssetIds),
        ]);

        return Inertia::render('Assets/StatusLog/Index', [
            'assets' => $assetList,
            'today' => $today,
            'logged_count' => count(array_intersect($loggedAssetIds, $assets->pluck('id')->toArray())),
            'total_count' => $assets->count(),
        ]);
    }

    /**
     * Show the status log form for a specific asset.
     *
     * If a log already exists for today, the form will be presented as an
     * amendment and will require an amendment_reason.
     */
    public function create(Request $request, Asset $asset): Response
    {
        $this->authorize('create', [AssetStatusLog::class, $asset]);

        $asset->load('county.region');

        $isAmendment = AssetStatusLog::query()
            ->where('asset_id', $asset->id)
            ->where('user_id', $request->user()->id)
            ->whereDate('logged_date', now()->toDateString())
            ->exists();

        $statuses = collect(AssetLogStatus::cases())->map(fn (AssetLogStatus $s) => [
            'value' => $s->value,
            'label' => match ($s) {
                AssetLogStatus::Operational => 'Operational',
                AssetLogStatus::Degraded => 'Degraded',
                AssetLogStatus::Down => 'Down',
                AssetLogStatus::Maintenance => 'Under Maintenance',
            },
            'color' => match ($s) {
                AssetLogStatus::Operational => 'green',
                AssetLogStatus::Degraded => 'amber',
                AssetLogStatus::Down => 'red',
                AssetLogStatus::Maintenance => 'blue',
            },
        ]);

        return Inertia::render('Assets/StatusLog/Create', [
            'asset' => $asset,
            'is_amendment' => $isAmendment,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Persist the status log entry.
     *
     * The server determines is_amendment by checking the database, ignoring
     * any client-supplied value. Sets synced_at to now() for online submissions.
     */
    public function store(StoreStatusLogRequest $request, Asset $asset): RedirectResponse
    {
        $this->authorize('create', [AssetStatusLog::class, $asset]);

        $today = now()->toDateString();

        $isAmendment = AssetStatusLog::query()
            ->where('asset_id', $asset->id)
            ->where('user_id', $request->user()->id)
            ->whereDate('logged_date', $today)
            ->exists();

        $validated = $request->validated();

        AssetStatusLog::create([
            'asset_id' => $asset->id,
            'user_id' => $request->user()->id,
            'logged_date' => $today,
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'observed_at' => $validated['observed_at'] ?? null,
            'throughput_mbps' => $validated['throughput_mbps'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_amendment' => $isAmendment,
            'amendment_reason' => $isAmendment ? ($validated['amendment_reason'] ?? null) : null,
            'synced_at' => now(),
        ]);

        return redirect()
            ->route('status-logs.index')
            ->with('success', $isAmendment ? 'Status log amended successfully.' : 'Status log saved successfully.');
    }
}
