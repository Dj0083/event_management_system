<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Lecturer;
use App\Models\Seminar;
use Illuminate\Http\Request;

class SeminarController extends Controller
{
    public function index()
    {
        return view('seminars.index', [
            'seminars' => Seminar::with(['event', 'sessions.lecturer'])->latest()->paginate(20),
            'events' => Event::orderByDesc('event_date')->get(), 'lecturers' => Lecturer::orderBy('name')->get(),
        ]);
    }

    public function storeLecturer(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'organization' => ['nullable', 'string', 'max:255']]);
        Lecturer::create($data);

        return back()->with('success', 'Lecturer or guest speaker added.');
    }

    public function storeSeminar(Request $request)
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id'], 'topic' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:3000']]);
        abort_unless(Event::whereKey($data['event_id'])->whereIn('type', ['seminar', 'workshop', 'other'])->exists(), 422, 'Select a seminar or workshop event.');
        Seminar::create($data);

        return back()->with('success', 'Seminar topic added.');
    }

    public function storeSession(Request $request)
    {
        $data = $request->validate([
            'seminar_id' => ['required', 'exists:seminars,id'], 'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'session_date' => ['required', 'date'], 'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'], 'location' => ['nullable', 'string', 'max:255'],
        ]);
        $seminar = Seminar::with('event')->findOrFail($data['seminar_id']);
        if ($seminar->event->event_date && $seminar->event->event_date->format('Y-m-d') !== $data['session_date']) {
            return back()->withErrors(['session_date' => 'The session must be on the seminar event date.'])->withInput();
        }
        $seminar->sessions()->create($data);

        return back()->with('success', 'Seminar session scheduled.');
    }
}
