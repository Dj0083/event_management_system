@extends('layouts.app')
@section('title', 'Events')
@section('crumb', 'Events')
@section('content')
<section class="events-intro">
    <div><div class="eyebrow">Discover and organize</div><h1>Events</h1><p>Bring campus activities to life with one place for schedules, participants, and results.</p></div>
    @if(auth()->user()->hasRole(['admin','staff']))<a class="btn" href="{{ route('events.create') }}">＋ Create event</a>@endif
</section>
@if(auth()->user()->hasRole(['admin','staff']))
<details class="panel category-tools" style="margin-bottom:18px"><summary>Manage event categories</summary><form method="POST" action="{{ route('event-categories.store') }}" class="category-form">@csrf<input name="name" maxlength="255" placeholder="Add a category, e.g. Student life" required><button class="btn secondary" type="submit">Add category</button></form><div class="category-pills">@forelse($categories as $category)<span>{{ $category->name }}</span>@empty<span class="muted">No categories yet.</span>@endforelse</div></details>
@endif
<section class="event-browser">
    <div class="event-browser-head"><div><h2>All events</h2><p>{{ $events->total() }} event{{ $events->total()===1?'':'s' }} in your workspace</p></div><form class="event-filters" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search events"><select name="status"><option value="">All statuses</option>@foreach(['draft','published','scheduled','completed','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="btn secondary" type="submit">Filter</button></form></div>
    @if($events->isEmpty())
        <div class="panel empty">No events match your search. @if(auth()->user()->hasRole(['admin','staff']))<a href="{{ route('events.create') }}" style="color:#6658d3">Create your first event</a>@endif</div>
    @else
        <div class="event-cards">
            @foreach($events as $event)
            <article class="event-card">
                <a class="event-cover" href="{{ route('events.show',$event) }}" aria-label="View {{ $event->name }}">
                    @if($event->image_path)<img src="{{ asset('storage/'.$event->image_path) }}" alt="{{ $event->name }} event cover" loading="lazy">@else<div class="event-cover-placeholder"><span>✦</span><small>{{ ucfirst($event->type) }}</small></div>@endif
                    <span class="event-status status-{{ $event->status }}">{{ ucfirst($event->status) }}</span>
                </a>
                <div class="event-card-content"><div class="event-meta"><span>{{ ucfirst($event->type) }}</span><span>·</span><span>{{ $event->category?->name ?: 'Uncategorized' }}</span></div>
                    <h3><a href="{{ route('events.show',$event) }}">{{ $event->name }}</a></h3>
                    <div class="event-info"><span>◷ &nbsp;{{ $event->event_date?->format('M j, Y') ?? 'Date to be announced' }}{{ $event->start_time ? ' · '.substr($event->start_time,0,5) : '' }}</span><span>⌖ &nbsp;{{ $event->location ?: 'Location to be announced' }}</span></div>
                    <div class="event-card-footer"><span>{{ $event->registrations_count }} participant{{ $event->registrations_count===1?'':'s' }}</span>@if(auth()->user()->hasRole(['admin','staff']))<a href="{{ route('events.edit',$event) }}">Edit event <span aria-hidden="true">→</span></a>@endif</div>
                </div>
            </article>
            @endforeach
        </div>
        <div class="pagination">{{ $events->links() }}</div>
    @endif
</section>
@endsection
