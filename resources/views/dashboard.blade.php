@extends('layouts.app')
@section('title', 'Overview')
@section('content')
<section class="head">
    <div><div class="eyebrow">{{ now()->format('l, F j, Y') }}</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}</h1><p>Here’s what’s happening across your events today.</p></div>
    @if(auth()->user()->hasRole(['admin','staff']))<a class="btn" href="{{ route('events.create') }}">＋ Create event</a>@endif
</section>
<section class="stats">
    <article class="stat"><div class="stat-label">Total events</div><div class="stat-value">{{ $stats['events'] }}</div><div class="stat-note">Across your workspace</div></article>
    <article class="stat"><div class="stat-label">Upcoming events</div><div class="stat-value">{{ $stats['upcoming'] }}</div><div class="stat-note">Scheduled and published</div></article>
    <article class="stat"><div class="stat-label">Registrations</div><div class="stat-value">{{ $stats['registrations'] }}</div><div class="stat-note">Across all events</div></article>
    <article class="stat"><div class="stat-label">Pending requests</div><div class="stat-value">{{ $stats['pending'] }}</div><div class="stat-note">Approvals awaiting action</div></article>
</section>
<section class="grid">
    <article class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Upcoming events</h2><div class="muted" style="font-size:11px;margin-top:4px">Your next events at a glance</div></div><a href="{{ route('events.index') }}" style="color:#6658d3;font-weight:700;font-size:12px">View all →</a></div>
        @if($events->isEmpty())<div class="empty">◷<br><br>No upcoming events yet. @if(auth()->user()->hasRole(['admin','staff']))<a href="{{ route('events.create') }}" style="color:#6658d3">Create your first event.</a>@endif</div>
        @else<div class="table-wrap"><table><thead><tr><th>Event</th><th>Date</th><th>Participants</th><th>Status</th></tr></thead><tbody>
        @foreach($events as $event)<tr><td><strong><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></strong><div class="muted">{{ ucfirst($event->type) }} · {{ $event->location ?: 'Location TBD' }}</div></td><td>{{ $event->event_date?->format('M j, Y') ?? 'TBD' }}</td><td>{{ $event->registrations_count }}</td><td><span class="pill {{ $event->status }}">{{ ucfirst($event->status) }}</span></td></tr>@endforeach
        </tbody></table></div>@endif
    </article>
    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Quick actions</h2><div class="muted" style="font-size:11px;margin-top:4px">Common tasks, one click away</div></div></div><div style="display:grid;gap:10px">
        @if(auth()->user()->hasRole(['admin','staff']))<a class="btn" href="{{ route('events.create') }}">＋ &nbsp; Create an event</a>@endif
        <a class="btn secondary" href="{{ route('events.index') }}">◷ &nbsp; Browse events</a>
        <div class="muted" style="font-size:12px;line-height:1.6;margin-top:12px">Use the navigation to manage participants, venues, judging, awards, and reports.</div>
    </div></article>
</section>
@endsection
