<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCountyRequest;
use App\Http\Requests\Admin\UpdateCountyRequest;
use App\Models\County;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CountyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', County::class);

        $counties = County::with('region')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Counties/Index', [
            'counties' => $counties,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', County::class);

        $regions = Region::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Counties/Create', [
            'regions' => $regions,
        ]);
    }

    public function store(StoreCountyRequest $request): RedirectResponse
    {
        County::create($request->validated());

        return redirect()->route('admin.counties.index');
    }

    public function edit(County $county): Response
    {
        $this->authorize('update', $county);

        $regions = Region::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Counties/Edit', [
            'county' => $county->load('region'),
            'regions' => $regions,
        ]);
    }

    public function update(UpdateCountyRequest $request, County $county): RedirectResponse
    {
        $county->update($request->validated());

        return redirect()->route('admin.counties.index');
    }

    public function destroy(County $county): RedirectResponse
    {
        $this->authorize('delete', $county);

        $county->delete();

        return redirect()->route('admin.counties.index');
    }
}
