@extends('layouts.app')
@section('title', 'Teacher leave')
@section('crumb', 'Teacher leave')
@section('content')
<section class="head">
    <div><div class="eyebrow">Staff administration</div><h1>Leave requests</h1><p>Record staff or guest-teacher leave requests and review their status.</p></div>
    <a class="btn secondary" href="{{ route('leave.template') }}">Download leave-letter template</a>
</section>
<section class="panel" style="margin-bottom:16px">
    <div class="panel-head"><h2 class="panel-title">Submit a leave request</h2></div>
    <p class="muted" style="margin-top:0">Guest teachers do not sign in. Download the letter template, have the guest teacher complete it, then enter their details here and attach the completed letter.</p>
    <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data">@csrf
        <div class="fields">
            <div class="field"><label>Teacher or guest lecturer name</label><input name="applicant_name" value="{{ old('applicant_name') }}" maxlength="255" placeholder="Leave blank to request for yourself"></div>
            <div class="field"><label>Teacher email</label><input name="applicant_email" type="email" value="{{ old('applicant_email') }}" maxlength="255" placeholder="Optional"></div>
            <div class="field"><label>Related event (optional)</label><select name="event_id"><option value="">No linked event</option>@foreach($events as $event)<option value="{{ $event->id }}">{{ $event->name }}</option>@endforeach</select></div>
            <div class="field"><label>Completed letter or supporting document</label><input name="document" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"><small class="muted">PDF, Word, or image file, up to 10 MB.</small></div>
            <div class="field"><label>Start date *</label><input name="start_date" type="date" min="{{ today()->format('Y-m-d') }}" required></div>
            <div class="field"><label>End date *</label><input name="end_date" type="date" min="{{ today()->format('Y-m-d') }}" required></div>
            <div class="field full"><label>Reason *</label><textarea name="reason" maxlength="3000" required placeholder="Please provide the reason for this leave request.">{{ old('reason') }}</textarea></div>
        </div>
        @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
        <div class="form-actions"><button class="btn" type="submit">Submit request</button></div>
    </form>
</section>
<section class="panel">
    <div class="panel-head"><h2 class="panel-title">{{ $isManager ? 'Requests for review' : 'Your request history' }}</h2></div>
    @if($requests->isEmpty())<div class="empty">No leave requests found.</div>
    @else<div class="table-wrap"><table><thead><tr>@if($isManager)<th>Applicant</th><th>Recorded by</th>@endif<th>Dates</th><th>Reason / event</th><th>Document</th><th>Status</th>@if($isManager)<th>Review</th>@endif</tr></thead><tbody>
        @foreach($requests as $leave)<tr>
            @if($isManager)<td><strong>{{ $leave->applicant_name ?: $leave->requester->name }}</strong><div class="muted">{{ $leave->applicant_email ?: $leave->requester->email }}</div></td><td>{{ $leave->requester->name }}</td>@endif
            <td>{{ $leave->start_date->format('M j, Y') }}<div class="muted">through {{ $leave->end_date->format('M j, Y') }}</div></td>
            <td>{{ $leave->reason }}@if($leave->event)<div class="muted">{{ $leave->event->name }}</div>@endif</td>
            <td>@if($leave->document_path)<a href="{{ route('leave.document',$leave) }}" style="color:#6658d3">Download</a>@else—@endif</td>
            <td><span class="pill {{ $leave->status==='approved'?'completed':($leave->status==='rejected'?'cancelled':'pending') }}">{{ ucfirst($leave->status) }}</span>@if($leave->review_note)<div class="muted">{{ $leave->review_note }}</div>@endif</td>
            @if($isManager)<td>@if($leave->status==='pending')<div style="display:flex;gap:5px"><form method="POST" action="{{ route('leave.review',$leave) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn" style="padding:6px 8px;font-size:10px">Approve</button></form><form method="POST" action="{{ route('leave.review',$leave) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="btn danger" style="padding:6px 8px;font-size:10px">Reject</button></form></div>@else—@endif</td>@endif
        </tr>@endforeach
    </tbody></table></div><div class="pagination">{{ $requests->links() }}</div>@endif
</section>
@endsection
