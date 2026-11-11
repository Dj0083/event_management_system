@extends('layouts.app')
@section('title', 'Participant name list')
@section('crumb', 'Participant name list')
@section('content')
<section class="head">
    <div><div class="eyebrow">Participants</div><h1>Participant name list</h1><p>View or download the registered names for an event.</p></div>
    <a class="btn secondary" href="{{ route('registrations.index') }}">Manage registrations</a>
</section>
<section class="panel" style="margin-bottom:16px">
    <form class="search" method="GET" action="{{ route('participants.namelist') }}">
        <label for="event_id" style="font-weight:700;align-self:center">Event</label>
        <select id="event_id" name="event_id" required style="border:1px solid #dfe2ec;border-radius:8px;padding:9px;min-width:250px">
            <option value="">Choose an event</option>
            @foreach($events as $item)<option value="{{ $item->id }}" @selected($event?->id===$item->id)>{{ $item->name }}</option>@endforeach
        </select>
        <button class="btn" type="submit">Show list</button>
        @if($event)<a class="btn secondary" href="{{ route('participants.namelist.export', ['event_id' => $event->id]) }}">Download CSV</a>@endif
    </form>
</section>
<section class="panel">
    <div class="panel-head"><div><h2 class="panel-title">{{ $event ? $event->name : 'Names' }}</h2><div class="muted" style="font-size:11px;margin-top:4px">{{ $event ? $registrations->count().' registered participant(s)' : 'Choose an event to view its participant list.' }}</div></div></div>
    @if($event && $registrations->isEmpty())<div class="empty">No participants are registered for this event yet.</div>
    @elseif($event)<div class="table-wrap"><table><thead><tr><th>#</th><th>Student number</th><th>Participant name</th><th>Department</th><th>Status</th></tr></thead><tbody>
        @foreach($registrations as $index => $registration)<tr><td>{{ $index + 1 }}</td><td>{{ $registration->student->student_number }}</td><td><strong>{{ $registration->student->name }}</strong></td><td>{{ $registration->student->department?->name ?: '—' }}</td><td><span class="pill {{ $registration->status==='approved'?'completed':'pending' }}">{{ ucfirst($registration->status) }}</span></td></tr>@endforeach
    </tbody></table></div>@endif
</section>
@endsection
