<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->only([
            'search',
            'user_id',
            'event',
            'auditable_type',
            'date_from',
            'date_to',
            'ip_address',
        ]);

        $logs = $this->buildQuery($filters)
            ->with('user:id,name')
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $events = AuditLog::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $auditableTypes = AuditLog::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $users,
            'events' => $events,
            'auditableTypes' => $auditableTypes,
        ]);
    }

    public function exportCsv(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->only([
            'search',
            'user_id',
            'event',
            'auditable_type',
            'date_from',
            'date_to',
            'ip_address',
        ]);

        return Excel::download(
            new AuditLogExport($filters),
            'audit-log-'.now()->format('Y-m-d').'.csv',
            \Maatwebsite\Excel\Excel::CSV,
        );
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->only([
            'search',
            'user_id',
            'event',
            'auditable_type',
            'date_from',
            'date_to',
            'ip_address',
        ]);

        $logs = $this->buildQuery($filters)
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(1000)
            ->get();

        $pdf = Pdf::loadView('exports.audit-log', [
            'logs' => $logs,
            'generatedAt' => now()->setTimezone('Africa/Nairobi')->format('d M Y, H:i').' EAT',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-log-'.now()->format('Y-m-d').'.pdf');
    }

    private function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return AuditLog::query()
            ->when(
                ! empty($filters['search']),
                fn ($q) => $q->where(
                    fn ($inner) => $inner
                        ->where('event', 'like', '%'.$filters['search'].'%')
                        ->orWhere('auditable_type', 'like', '%'.$filters['search'].'%')
                        ->orWhere('ip_address', 'like', '%'.$filters['search'].'%')
                        ->orWhere('user_agent', 'like', '%'.$filters['search'].'%')
                )
            )
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['event']), fn ($q) => $q->where('event', $filters['event']))
            ->when(! empty($filters['auditable_type']), fn ($q) => $q->where('auditable_type', $filters['auditable_type']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when(! empty($filters['ip_address']), fn ($q) => $q->where('ip_address', 'like', $filters['ip_address'].'%'));
    }
}
