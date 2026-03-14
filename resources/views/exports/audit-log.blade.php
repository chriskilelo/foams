<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

        .header { background: #1F3864; color: #fff; padding: 10px 14px; margin-bottom: 12px; }
        .header h1 { font-size: 14px; font-weight: bold; }
        .header p { font-size: 8px; color: #D6E4F7; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #D6E4F7; }
        thead th { padding: 5px 6px; text-align: left; font-size: 8px; font-weight: bold; color: #1F3864; border-bottom: 1px solid #2E5FA3; }
        tbody tr:nth-child(even) { background: #EEF4FB; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody td { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid #e5e7eb; }

        .mono { font-family: 'Courier New', monospace; font-size: 8px; }
        .muted { color: #6b7280; }
        .event-pill {
            display: inline-block;
            background: #EEF4FB;
            border: 1px solid #2E5FA3;
            color: #1F3864;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 7.5px;
        }
        .values { max-width: 140px; word-break: break-all; color: #374151; }
        .footer { margin-top: 10px; font-size: 7.5px; color: #6b7280; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>FOAMS — Audit Trail Export</h1>
        <p>ICT Authority of Kenya · eGovernment Department &nbsp;|&nbsp; Generated: {{ $generatedAt }} &nbsp;|&nbsp; {{ $logs->count() }} record(s)</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:32px">#</th>
                <th style="width:102px">Timestamp (EAT)</th>
                <th style="width:80px">User</th>
                <th style="width:115px">Event</th>
                <th style="width:68px">Model Type</th>
                <th style="width:32px">ID</th>
                <th style="width:80px">IP Address</th>
                <th style="width:140px">Old Values</th>
                <th style="width:140px">New Values</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
            <tr>
                <td class="mono muted">{{ $log->id }}</td>
                <td class="mono">{{ $log->created_at->setTimezone('Africa/Nairobi')->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->user?->name ?? 'System' }}</td>
                <td><span class="event-pill">{{ $log->event }}</span></td>
                <td>{{ class_basename($log->auditable_type) }}</td>
                <td class="mono muted">{{ $log->auditable_id }}</td>
                <td class="mono muted">{{ $log->ip_address ?? '—' }}</td>
                <td class="values mono">{{ $log->old_values ? json_encode($log->old_values, JSON_PRETTY_PRINT) : '—' }}</td>
                <td class="values mono">{{ $log->new_values ? json_encode($log->new_values, JSON_PRETTY_PRINT) : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:16px; color:#6b7280;">No audit log entries found for the selected filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">FOAMS Audit Trail — Confidential · ICT Authority of Kenya</div>

</body>
</html>
