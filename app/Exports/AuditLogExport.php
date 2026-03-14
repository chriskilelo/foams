<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters) {}

    public function query(): Builder
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->when(
                ! empty($this->filters['search']),
                fn ($q) => $q->where(
                    fn ($inner) => $inner
                        ->where('event', 'like', '%'.$this->filters['search'].'%')
                        ->orWhere('auditable_type', 'like', '%'.$this->filters['search'].'%')
                        ->orWhere('ip_address', 'like', '%'.$this->filters['search'].'%')
                        ->orWhere('user_agent', 'like', '%'.$this->filters['search'].'%')
                )
            )
            ->when(! empty($this->filters['user_id']), fn ($q) => $q->where('user_id', $this->filters['user_id']))
            ->when(! empty($this->filters['event']), fn ($q) => $q->where('event', $this->filters['event']))
            ->when(! empty($this->filters['auditable_type']), fn ($q) => $q->where('auditable_type', $this->filters['auditable_type']))
            ->when(! empty($this->filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $this->filters['date_from']))
            ->when(! empty($this->filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $this->filters['date_to']))
            ->when(! empty($this->filters['ip_address']), fn ($q) => $q->where('ip_address', 'like', $this->filters['ip_address'].'%'))
            ->latest('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Timestamp (EAT)',
            'User',
            'Event',
            'Model Type',
            'Model ID',
            'IP Address',
            'User Agent',
            'Old Values',
            'New Values',
        ];
    }

    /** @param AuditLog $row */
    public function map($row): array
    {
        return [
            $row->id,
            $row->created_at->setTimezone('Africa/Nairobi')->format('Y-m-d H:i:s'),
            $row->user?->name ?? 'System',
            $row->event,
            class_basename($row->auditable_type),
            $row->auditable_id,
            $row->ip_address ?? '',
            $row->user_agent ?? '',
            $row->old_values ? json_encode($row->old_values) : '',
            $row->new_values ? json_encode($row->new_values) : '',
        ];
    }
}
