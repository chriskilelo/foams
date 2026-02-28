<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\County;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Asset::class);

        $assets = Asset::query()
            ->with(['county.region'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('county_id'), fn ($q) => $q->where('county_id', $request->integer('county_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('asset_code', 'like', '%'.$request->input('search').'%')
                ->orWhere('location_name', 'like', '%'.$request->input('search').'%')
            ))
            ->orderBy('asset_code')
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

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'counties' => $counties,
            'filters' => $request->only(['type', 'status', 'county_id', 'search']),
        ]);
    }

    public function show(Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load(['county.region', 'assignedTo:id,name']);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
            'open_issues_count' => Inertia::defer(fn () => $asset->issues()
                ->whereNotIn('status', ['resolved', 'closed', 'duplicate'])
                ->count()),
            'recent_logs' => Inertia::defer(fn () => $asset->statusLogs()
                ->with('user:id,name')
                ->orderByDesc('logged_date')
                ->limit(5)
                ->get()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Asset::class);

        $counties = County::query()->orderBy('name')->get(['id', 'name']);

        $types = collect(AssetType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => match ($t) {
                AssetType::WifiHotspot => 'Public WiFi Hotspot',
                AssetType::NofbiNode => 'NOFBI Node',
                AssetType::OgnEquipment => 'OGN Equipment',
            },
        ]);

        $statuses = collect(AssetStatus::cases())
            ->reject(fn ($s) => $s === AssetStatus::Decommissioned)
            ->map(fn ($s) => [
                'value' => $s->value,
                'label' => ucfirst($s->value),
            ]);

        return Inertia::render('Assets/Create', [
            'counties' => $counties,
            'types' => $types,
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = Asset::create($request->validated());

        return redirect()->route('assets.show', $asset);
    }

    public function edit(Asset $asset): Response
    {
        $this->authorize('update', $asset);

        $asset->load('county');

        $user = auth()->user();

        $counties = County::query()
            ->when(
                $user->hasAnyRole(['ricto', 'icto', 'aicto']) && $user->region_id,
                fn ($q) => $q->where('region_id', $user->region_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $types = collect(AssetType::cases())->map(fn ($t) => [
            'value' => $t->value,
            'label' => match ($t) {
                AssetType::WifiHotspot => 'Public WiFi Hotspot',
                AssetType::NofbiNode => 'NOFBI Node',
                AssetType::OgnEquipment => 'OGN Equipment',
            },
        ]);

        $statuses = collect(AssetStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ]);

        return Inertia::render('Assets/Edit', [
            'asset' => $asset,
            'counties' => $counties,
            'types' => $types,
            'statuses' => $statuses,
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->validated());

        return redirect()->route('assets.show', $asset);
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        return redirect()->route('assets.index');
    }
}
