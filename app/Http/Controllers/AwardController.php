<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Event;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AwardController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderByDesc('event_date')->get();
        $event = $events->firstWhere('id', $request->integer('event_id'));

        return view('awards.index', [
            'events' => $events, 'event' => $event,
            'awards' => Award::with(['registration.student', 'event'])->when($event, fn ($query) => $query->where('event_id', $event->id))->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'first' => ['required', 'numeric', 'min:0'], 'second' => ['required', 'numeric', 'min:0'], 'third' => ['required', 'numeric', 'min:0'],
        ]);
        $event = Event::findOrFail($data['event_id']);
        $results = Result::with('registration.student')->where('event_id', $event->id)->where('status', 'final')->whereIn('rank', [1, 2, 3])->orderBy('rank')->get();
        if ($results->isEmpty()) {
            return back()->withErrors(['event_id' => 'Finalize competition results before generating awards.'])->withInput();
        }
        $amounts = [1 => $data['first'], 2 => $data['second'], 3 => $data['third']];
        DB::transaction(function () use ($results, $event, $amounts) {
            foreach ($results as $result) {
                Award::updateOrCreate(['event_id' => $event->id, 'registration_id' => $result->registration_id], [
                    'name' => match ($result->rank) {1 => 'First place', 2 => 'Second place', default => 'Third place'},
                    'place' => $result->rank, 'cash_amount' => $amounts[$result->rank] ?? 0,
                ]);
            }
        });

        return redirect()->route('awards.index', ['event_id' => $event->id])->with('success', 'Awards generated from finalized results.');
    }

    public function distribute(Request $request, Award $award)
    {
        $data = $request->validate(['distribution_status' => ['required', Rule::in(['distributed', 'pending'])]]);
        $award->update($data + ['distributed_at' => $data['distribution_status'] === 'distributed' ? today() : null]);

        return back()->with('success', 'Award distribution record updated.');
    }
}
