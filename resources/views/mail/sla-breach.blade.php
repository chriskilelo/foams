@component('mail::message')
# SLA Breach Alert

An issue has exceeded its SLA resolution deadline and requires **immediate attention**.

@component('mail::panel')
**Reference:** {{ $issue->reference_number }}
**Severity:** {{ ucfirst($issue->severity->value) }}
**Type:** {{ ucwords(str_replace('_', ' ', $issue->issue_type)) }}
**County:** {{ $issue->county?->name ?? 'N/A' }}
**Status:** {{ ucwords(str_replace('_', ' ', $issue->status->value)) }}
**SLA Deadline:** {{ $issue->sla_due_at?->setTimezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT
**Breached At:** {{ now()->setTimezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT
@endcomponent

This issue has been automatically flagged as SLA breached. Please take immediate action to resolve or escalate this issue.

@component('mail::button', ['url' => config('app.url').'/issues/'.$issue->id, 'color' => 'red'])
View Issue
@endcomponent

---
*FOAMS — ICTA Field Operations & Asset Management System*
*This is an automated SLA monitoring alert.*
@endcomponent
