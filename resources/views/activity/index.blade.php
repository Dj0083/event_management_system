@extends('layouts.app')
@section('title', 'Activity history')
@section('crumb', 'Activity history')
@section('content')
<section class="head"><div><div class="eyebrow">Audit trail</div><h1>Activity history</h1><p>Recent changes and exports made in the system.</p></div></section>
<section class="panel">@if($activities->isEmpty())<div class="empty">No activity has been recorded yet.</div>@else<div class="table-wrap"><table><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Record</th><th>Request</th></tr></thead><tbody>@foreach($activities as $activity)<tr><td>{{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('M j, Y H:i:s') }}</td><td><strong>{{ $activity->user_name ?: 'Deleted user' }}</strong><div class="muted">{{ $activity->user_email }}</div></td><td>{{ str($activity->action)->replace(['.','_'],[' / ',' '])->title() }}</td><td>{{ $activity->subject_type ? $activity->subject_type.' #'.$activity->subject_id : '—' }}</td><td class="muted">{{ $activity->details }}</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $activities->links() }}</div>@endif</section>
@endsection
