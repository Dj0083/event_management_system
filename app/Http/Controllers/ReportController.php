<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', ['events' => Event::orderByDesc('event_date')->get()]);
    }

    public function download(Request $request, string $type)
    {
        abort_unless(in_array($type, ['events', 'registrations', 'attendance', 'results', 'awards', 'venues', 'refreshments', 'leave', 'certificates', 'seminars'], true), 404);
        abort_if(in_array($type, ['awards', 'leave'], true) && ! $request->user()->hasRole(['admin', 'staff']), 403);
        $eventId = $request->integer('event_id') ?: null;
        $dateTag = now()->format('Ymd');
        $filename = "evently-{$type}-{$dateTag}.csv";

        $rows = match ($type) {
            'events' => DB::table('events')->when($eventId, fn ($q) => $q->where('id', $eventId))->orderBy('event_date')->get(['id', 'name', 'type', 'event_date', 'start_time', 'end_time', 'location', 'status'])->map(fn ($row) => (array) $row),
            'registrations' => DB::table('registrations')->join('events', 'events.id', '=', 'registrations.event_id')->join('students', 'students.id', '=', 'registrations.student_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->get(['students.student_number', 'students.name as student_name', 'events.name as event_name', 'registrations.status', 'registrations.registered_at'])->map(fn ($row) => (array) $row),
            'attendance' => DB::table('attendance')->join('registrations', 'registrations.id', '=', 'attendance.registration_id')->join('students', 'students.id', '=', 'registrations.student_id')->join('events', 'events.id', '=', 'registrations.event_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->get(['students.student_number', 'students.name as student_name', 'events.name as event_name', 'attendance.status', 'attendance.checked_in_at'])->map(fn ($row) => (array) $row),
            'results' => DB::table('results')->join('registrations', 'registrations.id', '=', 'results.registration_id')->join('students', 'students.id', '=', 'registrations.student_id')->join('events', 'events.id', '=', 'results.event_id')->where('results.status', 'final')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->orderBy('results.rank')->get(['students.student_number', 'students.name as student_name', 'events.name as event_name', 'results.total_score', 'results.rank'])->map(fn ($row) => (array) $row),
            'awards' => DB::table('awards')->join('events', 'events.id', '=', 'awards.event_id')->leftJoin('registrations', 'registrations.id', '=', 'awards.registration_id')->leftJoin('students', 'students.id', '=', 'registrations.student_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->orderBy('awards.place')->get(['events.name as event_name', 'students.student_number', 'students.name as winner', 'awards.name as award', 'awards.cash_amount', 'awards.distribution_status', 'awards.distributed_at'])->map(fn ($row) => (array) $row),
            'venues' => DB::table('venue_bookings')->join('venues', 'venues.id', '=', 'venue_bookings.venue_id')->leftJoin('events', 'events.id', '=', 'venue_bookings.event_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('venue_bookings.booking_date')->get(['venues.name as venue', 'events.name as event_name', 'venue_bookings.booking_date', 'venue_bookings.start_time', 'venue_bookings.end_time', 'venue_bookings.purpose', 'venue_bookings.status'])->map(fn ($row) => (array) $row),
            'refreshments' => DB::table('refreshment_plans')->join('events', 'events.id', '=', 'refreshment_plans.event_id')->join('refreshment_items', 'refreshment_items.id', '=', 'refreshment_plans.refreshment_item_id')->leftJoin('refreshment_distributions', 'refreshment_distributions.refreshment_plan_id', '=', 'refreshment_plans.id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->groupBy('refreshment_plans.id', 'events.name', 'refreshment_items.name', 'refreshment_items.unit', 'refreshment_plans.quantity', 'refreshment_plans.estimated_cost', 'refreshment_plans.status')->orderBy('events.name')->get(['events.name as event_name', 'refreshment_items.name as item', 'refreshment_items.unit', 'refreshment_plans.quantity as planned', DB::raw('coalesce(sum(refreshment_distributions.quantity), 0) as distributed'), 'refreshment_plans.estimated_cost', 'refreshment_plans.status'])->map(fn ($row) => (array) $row),
            'leave' => DB::table('leave_requests')->join('users', 'users.id', '=', 'leave_requests.user_id')->leftJoin('events', 'events.id', '=', 'leave_requests.event_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('leave_requests.start_date')->get(['users.name as staff_name', 'events.name as event_name', 'leave_requests.start_date', 'leave_requests.end_date', 'leave_requests.reason', 'leave_requests.status'])->map(fn ($row) => (array) $row),
            'certificates' => DB::table('certificates')->join('events', 'events.id', '=', 'certificates.event_id')->join('registrations', 'registrations.id', '=', 'certificates.registration_id')->join('students', 'students.id', '=', 'registrations.student_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->get(['events.name as event_name', 'students.student_number', 'students.name as recipient', 'certificates.type', 'certificates.certificate_number', 'certificates.created_at'])->map(fn ($row) => (array) $row),
            default => DB::table('seminars')->join('events', 'events.id', '=', 'seminars.event_id')->leftJoin('seminar_sessions', 'seminar_sessions.seminar_id', '=', 'seminars.id')->leftJoin('lecturers', 'lecturers.id', '=', 'seminar_sessions.lecturer_id')->when($eventId, fn ($q) => $q->where('events.id', $eventId))->orderBy('events.name')->get(['events.name as event_name', 'seminars.topic', 'lecturers.name as speaker', 'seminar_sessions.session_date', 'seminar_sessions.start_time', 'seminar_sessions.end_time', 'seminar_sessions.location'])->map(fn ($row) => (array) $row),
        };
        return Response::streamDownload(function () use ($rows) {
            $safeCell = fn ($value) => is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value) ? "'".$value : $value;
            $output = fopen('php://output', 'w');
            if ($rows->isNotEmpty()) fputcsv($output, array_keys((array) $rows->first()));
            foreach ($rows as $row) fputcsv($output, array_map($safeCell, array_values($row)));
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
