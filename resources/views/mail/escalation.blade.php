@component('mail::message')
# Issue Escalated to Director

An issue has been escalated and requires your attention as Director.

@component('mail::panel')
**Reference:** {{ $issue->reference_number }}
**Severity:** {{ ucfirst($issue->severity->value) }}
**Type:** {{ ucwords(str_replace('_', ' ', $issue->issue_type)) }}
**County:** {{ $issue->county?->name ?? 'N/A' }}
**Escalated By:** {{ $issue->escalatedBy?->name ?? 'System' }}
**Escalated At:** {{ $issue->escalated_at?->setTimezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT
@endcomponent

**Escalation Reason:**
{{ $reason }}

Please review this escalated issue and provide guidance to the field team.

@component('mail::button', ['url' => config('app.url').'/issues/'.$issue->id, 'color' => 'blue'])
Review Escalated Issue
@endcomponent

---
*FOAMS — ICTA Field Operations & Asset Management System*
*This is an automated escalation notification.*
@endcomponent
