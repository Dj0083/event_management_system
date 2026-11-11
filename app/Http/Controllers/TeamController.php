<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Student;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::where('type', 'competition')->orderByDesc('event_date')->get();
        $event = $events->firstWhere('id', $request->integer('event_id')) ?? $events->first();

        return view('teams.index', [
            'events' => $events, 'event' => $event,
            'teams' => Team::with(['leader', 'members.student'])->when($event, fn ($query) => $query->where('event_id', $event->id))->latest()->paginate(20)->withQueryString(),
            'students' => Student::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'], 'name' => ['required', 'string', 'max:255'],
            'leader_student_id' => ['required', 'exists:students,id'], 'members' => ['required', 'array', 'min:2', 'max:20'],
            'members.*' => ['required', 'distinct', 'exists:students,id'],
        ]);
        abort_unless(in_array($data['leader_student_id'], $data['members']), 422, 'The team leader must be included in the member list.');
        $event = Event::findOrFail($data['event_id']);
        abort_unless($event->type === 'competition' && $event->status !== 'cancelled', 422, 'Choose an active competition event.');
        abort_if($event->registration_opens_at && now()->lessThan($event->registration_opens_at), 422, 'Registration for this event has not opened yet.');
        abort_if($event->registration_closes_at && now()->greaterThan($event->registration_closes_at), 422, 'Registration for this event has closed.');
        $alreadyRegistered = Registration::where('event_id', $event->id)->whereIn('student_id', $data['members'])->pluck('student_id')->all();
        abort_if($alreadyRegistered, 422, 'A selected student is already registered for this event.');
        if ($event->capacity && $event->registrations()->whereIn('status', ['pending', 'approved'])->count() + count($data['members']) > $event->capacity) {
            return back()->withErrors(['members' => 'The event does not have enough remaining participant capacity for this team.'])->withInput();
        }

        DB::transaction(function () use ($data, $event) {
            $team = Team::create(['event_id' => $event->id, 'name' => $data['name'], 'leader_student_id' => $data['leader_student_id']]);
            foreach ($data['members'] as $studentId) {
                Registration::create(['event_id' => $event->id, 'student_id' => $studentId, 'team_id' => $team->id, 'status' => 'pending', 'registered_at' => now()]);
            }
        });

        return back()->with('success', 'Team and member registrations submitted for approval.');
    }
}
