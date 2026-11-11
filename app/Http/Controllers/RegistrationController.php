<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Student;
use App\Models\User;
use App\Notifications\WorkflowNotice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $registrations = Registration::with(['event', 'student', 'attendance'])
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->latest()->paginate(20)->withQueryString();

        return view('registrations.index', [
            'registrations' => $registrations,
            'events' => Event::orderByDesc('event_date')->get(),
            'students' => Student::orderBy('name')->get(),
        ]);
    }

    public function nameList(Request $request)
    {
        $event = $request->filled('event_id') ? Event::findOrFail($request->integer('event_id')) : null;
        $registrations = $event
            ? Registration::with(['event', 'student.department'])
                ->where('event_id', $event->id)
                ->whereIn('status', ['approved', 'pending'])
                ->orderBy('status')->orderBy('id')->get()
            : collect();

        return view('participants.name-list', [
            'events' => Event::orderByDesc('event_date')->get(),
            'event' => $event,
            'registrations' => $registrations,
        ]);
    }

    public function exportNameList(Request $request): StreamedResponse
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id']]);
        $event = Event::findOrFail($data['event_id']);
        $registrations = Registration::with('student.department')
            ->where('event_id', $event->id)
            ->whereIn('status', ['approved', 'pending'])
            ->orderBy('status')->orderBy('id')->get();

        return response()->streamDownload(function () use ($event, $registrations): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student number', 'Student name', 'Department', 'Registration status', 'Event']);
            foreach ($registrations as $registration) {
                fputcsv($output, [
                    $registration->student->student_number,
                    $registration->student->name,
                    $registration->student->department?->name ?? '',
                    ucfirst($registration->status),
                    $event->name,
                ]);
            }
            fclose($output);
        }, 'participant-name-list.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'student_id' => ['required', 'exists:students,id', Rule::unique('registrations')->where('event_id', $request->input('event_id'))],
            'status' => ['required', Rule::in(['pending', 'approved'])],
        ]);
        $event = Event::findOrFail($data['event_id']);
        abort_unless($event->status !== 'cancelled', 422, 'Registrations are closed for cancelled events.');
        if ($event->registration_closes_at && now()->greaterThan($event->registration_closes_at)) {
            return back()->withErrors(['event_id' => 'Registration for this event has closed.'])->withInput();
        }
        if ($event->capacity && $event->registrations()->whereIn('status', ['pending', 'approved'])->count() >= $event->capacity) {
            return back()->withErrors(['event_id' => 'This event has reached its participant capacity.'])->withInput();
        }

        Registration::create($data + ['registered_at' => now()]);
        User::whereIn('role', ['admin', 'staff'])->where('is_active', true)->get()->each(fn ($user) => $user->notify(new WorkflowNotice('New event registration', "A student was registered for {$event->name}.", route('registrations.index'))));

        return back()->with('success', 'Participant registered.');
    }

    public function review(Request $request, Registration $registration)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $registration->update($data);
        return back()->with('success', 'Registration status updated.');
    }

    public function attendance(Request $request, Registration $registration)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['present', 'absent'])]]);
        $registration->attendance()->updateOrCreate([], [
            'status' => $data['status'],
            'checked_in_at' => $data['status'] === 'present' ? now() : null,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Attendance recorded.');
    }
}
