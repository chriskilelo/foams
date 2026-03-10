@component('mail::message')
# Issue Report Received

Dear {{ $issue->reporter_name ?? 'Valued Reporter' }},

Thank you for reporting an issue to the **ICT Authority Field Operations & Asset Management System**. Your report has been received and logged.

**Reference Number:** {{ $issue->reference_number }}
**Issue Type:** {{ ucwords(str_replace('_', ' ', $issue->issue_type)) }}
**Severity:** {{ ucfirst($issue->severity->value) }}
**Location:** {{ $issue->county?->name ?? 'N/A' }}
**Reported On:** {{ $issue->created_at->setTimezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT

Our field team has been notified and will respond within the SLA window for this severity level.

You can track the status of your issue using your reference number on the FOAMS public portal.

@component('mail::button', ['url' => config('app.url').'/track/'.$issue->reference_number, 'color' => 'blue'])
Track Your Issue
@endcomponent

If you have any additional information to provide, please reply to this email quoting your reference number.

---
*ICT Authority of Kenya — eGovernment Department*
*This is an automated message. Do not reply directly to this email.*
@endcomponent
