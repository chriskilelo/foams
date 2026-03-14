@component('mail::message')
# Issue Assigned to You

You have been assigned an issue that requires your attention.

@component('mail::panel')
**Reference:** {{ $issue->reference_number }}
**Severity:** {{ ucfirst($issue->severity->value) }}
**Status:** {{ ucwords(str_replace('_', ' ', $issue->status->value)) }}
**Type:** {{ ucwords(str_replace('_', ' ', $issue->issue_type)) }}
**Asset:** {{ $issue->asset?->name ?? 'N/A' }} ({{ $issue->asset?->asset_code ?? 'N/A' }})
**Location:** {{ $issue->county?->name ?? 'N/A' }}, {{ $issue->county?->region?->name ?? 'N/A' }}
@endcomponent

Please review this issue and take appropriate action within the SLA deadline.

@component('mail::button', ['url' => config('app.url').'/issues/'.$issue->id, 'color' => 'blue'])
View Issue
@endcomponent

---
*FOAMS — ICTA Field Operations & Asset Management System*
*This is an automated assignment notification.*
@endcomponent
