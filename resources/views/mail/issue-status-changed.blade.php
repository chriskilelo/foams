@component('mail::message')
# Issue Status Update

Dear {{ $issue->reporter_name ?? 'Valued Reporter' }},

The status of your reported issue has been updated.

**Reference Number:** {{ $issue->reference_number }}
**Previous Status:** {{ ucwords(str_replace('_', ' ', $previousStatus->value)) }}
**Current Status:** {{ ucwords(str_replace('_', ' ', $newStatus->value)) }}
**Updated On:** {{ now()->setTimezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT

@if($issue->status->value === 'resolved')
Your issue has been resolved. If you believe the issue has not been fully addressed, you can request a review within 5 business days.
@elseif($issue->status->value === 'closed')
Your issue has been closed. Thank you for bringing this to our attention.
@else
Our team is actively working on your issue. We will notify you of further updates.
@endif

@component('mail::button', ['url' => config('app.url').'/track/'.$issue->reference_number, 'color' => 'blue'])
View Issue Status
@endcomponent

---
*ICT Authority of Kenya — eGovernment Department*
*Reference: {{ $issue->reference_number }}*
@endcomponent
