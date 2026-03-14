<?php

namespace App\Http\Controllers\Issues;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Resolution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResolutionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewGlobalFeed', Issue::class);

        $resolutions = Resolution::query()
            ->with([
                'issue:id,reference_number,issue_type,severity,status',
                'resolvedBy:id,name',
            ])
            ->when(
                $request->filled('type'),
                fn ($q) => $q->where('resolution_type', $request->type)
            )
            ->latest('resolved_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Resolutions/Index', [
            'resolutions' => $resolutions,
            'filters' => $request->only('type'),
        ]);
    }
}
