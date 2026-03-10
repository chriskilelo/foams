@component('mail::message')
@if($isRictoSummary)
# Daily Status Log Summary — Action Required

Dear {{ $user->name }},

The following field officers in your region **have not submitted their daily status logs** as of {{ now()->setTimezone('Africa/Nairobi')->format('H:i') }} EAT today ({{ today()->format('d M Y') }}).

@if($officersWithoutLogs->isNotEmpty())
@component('mail::table')
| Officer Name | Role |
|:-------------|:-----|
@foreach($officersWithoutLogs as $officer)
| {{ $officer->name }} | {{ ucfirst($officer->getRoleNames()->first() ?? 'N/A') }} |
@endforeach
@endcomponent
@else
All officers in your region have submitted their logs.
@endif

Please follow up with the officers listed above to ensure compliance.

@else
# Daily Status Log Reminder

Dear {{ $user->name }},

This is a reminder that you have **not yet submitted your daily asset status logs** for today ({{ today()->format('d M Y') }}).

Please log into FOAMS and submit status logs for all assets in your area of responsibility before end of business today.

@endif

@component('mail::button', ['url' => config('app.url').'/status-logs/create', 'color' => 'blue'])
Submit Status Logs
@endcomponent

---
*FOAMS — ICTA Field Operations & Asset Management System*
*Daily compliance reminder — {{ today()->setTimezone('Africa/Nairobi')->format('d M Y') }}*
@endcomponent
