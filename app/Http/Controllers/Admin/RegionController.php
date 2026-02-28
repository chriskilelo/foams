<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRegionRequest;
use App\Http\Requests\Admin\UpdateRegionRequest;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Region::class);

        $regions = Region::withCount('counties')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Regions/Index', [
            'regions' => $regions,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Region::class);

        return Inertia::render('Admin/Regions/Create');
    }

    public function store(StoreRegionRequest $request): RedirectResponse
    {
        Region::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('admin.regions.index');
    }

    public function edit(Region $region): Response
    {
        $this->authorize('update', $region);

        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region,
        ]);
    }

    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse
    {
        $region->update($request->validated() + ['is_active' => $request->boolean('is_active', $region->is_active)]);

        return redirect()->route('admin.regions.index');
    }

    public function destroy(Region $region): RedirectResponse
    {
        $this->authorize('delete', $region);

        $region->delete();

        return redirect()->route('admin.regions.index');
    }
}
