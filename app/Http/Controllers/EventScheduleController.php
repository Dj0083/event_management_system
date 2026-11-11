<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSchedule;
use Illuminate\Http\Request;

class EventScheduleController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'],
            'schedule_date' => ['nullable', 'date'], 'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'], 'location' => ['nullable', 'string', 'max:255'],
        ]);
        $event->schedules()->create($data);

        return back()->with('success', 'Event schedule item added.');
    }

    public function update(Request $request, EventSchedule $schedule)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'],
            'schedule_date' => ['nullable', 'date'], 'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'], 'location' => ['nullable', 'string', 'max:255'],
        ]);
        $schedule->update($data);

        return back()->with('success', 'Event schedule item updated.');
    }

    public function destroy(EventSchedule $schedule)
    {
        $schedule->delete();

        return back()->with('success', 'Event schedule item deleted.');
    }
}
