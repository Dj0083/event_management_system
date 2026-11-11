<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\JudgeScore;
use App\Models\JudgingCriterion;
use App\Models\Registration;
use App\Models\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\WorkflowNotice;

class JudgingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $eventQuery = Event::where('type', 'competition')->orderByDesc('event_date');
        $events = $eventQuery->get();
        $event = $events->firstWhere('id', $request->integer('event_id')) ?? $events->first();

        return view('judging.index', [
            'events' => $events,
            'event' => $event,
            'criteria' => $event ? JudgingCriterion::where('event_id', $event->id)->orderBy('id')->get() : collect(),
            'registrations' => $event ? Registration::with(['student', 'event'])
                ->where('event_id', $event->id)->where('status', 'approved')->orderBy('id')->get() : collect(),
            'scores' => $event ? JudgeScore::whereIn('judging_criterion_id', JudgingCriterion::where('event_id', $event->id)->select('id'))->get() : collect(),
            'judges' => User::where('role', 'staff')->where('is_active', true)->orderBy('name')->get(),
            'assignments' => $event ? DB::table('judge_assignments')->join('users', 'users.id', '=', 'judge_assignments.user_id')->where('event_id', $event->id)->select('users.id', 'users.name', 'users.email')->get() : collect(),
            'results' => $event ? Result::with('registration.student')->where('event_id', $event->id)->orderByRaw('rank is null, rank')->get() : collect(),
        ]);
    }

    public function criterion(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'], 'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'], 'max_score' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ]);
        JudgingCriterion::create($data);

        return back()->with('success', 'Judging criterion added.');
    }

    public function assignJudge(Request $request)
    {
        $data = $request->validate(['event_id' => ['required', 'exists:events,id'], 'user_id' => ['required', 'exists:users,id']]);
        abort_unless(User::whereKey($data['user_id'])->where('role', 'staff')->where('is_active', true)->exists(), 422, 'Choose an active staff account to assign as a judge.');
        DB::table('judge_assignments')->updateOrInsert(['event_id' => $data['event_id'], 'user_id' => $data['user_id']], ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Judge assigned to this competition.');
    }

    public function score(Request $request)
    {
        $data = $request->validate([
            'judging_criterion_id' => ['required', 'exists:judging_criteria,id'],
            'registration_id' => ['required', 'exists:registrations,id'],
            'score' => ['required', 'numeric', 'min:0'], 'comments' => ['nullable', 'string', 'max:2000'],
        ]);
        $criterion = JudgingCriterion::findOrFail($data['judging_criterion_id']);
        $registration = Registration::findOrFail($data['registration_id']);
        abort_unless($criterion->event_id === $registration->event_id, 422, 'Criterion and participant must belong to the same event.');
        abort_if((float) $data['score'] > (float) $criterion->max_score, 422, 'Score exceeds the maximum for this criterion.');
        abort_unless($request->user()->hasRole(['admin', 'staff']) || DB::table('judge_assignments')->where('event_id', $criterion->event_id)->where('user_id', $request->user()->id)->exists(), 403);
        abort_if(Result::where('event_id', $criterion->event_id)->where('status', 'final')->exists(), 422, 'Final results are locked.');

        JudgeScore::updateOrCreate([
            'judging_criterion_id' => $criterion->id,
            'judge_user_id' => $request->user()->id,
            'registration_id' => $registration->id,
        ], ['score' => $data['score'], 'comments' => $data['comments'] ?? null]);

        return back()->with('success', 'Score saved.');
    }

    public function calculate(Request $request, Event $event)
    {
        $this->authorizeManagerOrAssignedJudge($request, $event);
        abort_if(Result::where('event_id', $event->id)->where('status', 'final')->exists(), 422, 'Final results are locked.');
        $criteria = JudgingCriterion::where('event_id', $event->id)->get();
        $participants = Registration::where('event_id', $event->id)->where('status', 'approved')->get();
        abort_if($criteria->isEmpty() || $participants->isEmpty(), 422, 'Add at least one criterion and one approved participant before calculating results.');
        foreach ($participants as $participant) {
            foreach ($criteria as $criterion) {
                if (! JudgeScore::where('judging_criterion_id', $criterion->id)->where('registration_id', $participant->id)->exists()) {
                    abort(422, "Every approved participant needs a score for {$criterion->name} before rankings can be calculated.");
                }
            }
        }
        $totals = [];
        foreach ($participants as $participant) {
            $weightedTotal = 0.0;
            $weightTotal = 0.0;
            foreach ($criteria as $criterion) {
                $average = JudgeScore::where('judging_criterion_id', $criterion->id)->where('registration_id', $participant->id)->avg('score');
                if ($average !== null) {
                    $weightedTotal += ((float) $average / (float) $criterion->max_score) * (float) $criterion->weight;
                    $weightTotal += (float) $criterion->weight;
                }
            }
            $totals[$participant->id] = $weightTotal ? round(($weightedTotal / $weightTotal) * 100, 2) : 0;
        }
        arsort($totals, SORT_NUMERIC);
        $rank = 0;
        $lastScore = null;
        foreach ($totals as $registrationId => $total) {
            if ($lastScore === null || $total < $lastScore) $rank++;
            Result::updateOrCreate(['event_id' => $event->id, 'registration_id' => $registrationId], [
                'total_score' => $total, 'rank' => $rank, 'status' => 'provisional', 'finalized_by' => null, 'finalized_at' => null,
            ]);
            $lastScore = $total;
        }

        return back()->with('success', 'Provisional rankings calculated from the submitted scores.');
    }

    public function finalize(Request $request, Event $event)
    {
        abort_unless($request->user()->hasRole(['admin', 'staff']), 403);
        abort_unless(Result::where('event_id', $event->id)->exists(), 422, 'Calculate provisional rankings before finalizing results.');
        Result::where('event_id', $event->id)->update(['status' => 'final', 'finalized_by' => $request->user()->id, 'finalized_at' => now()]);
        $results = Result::with('registration.student.user')->where('event_id', $event->id)->get();
        foreach ($results as $result) {
            $result->registration?->student?->user?->notify(new WorkflowNotice('Competition results finalized', "Final results are available for {$event->name}.", route('events.show', $event)));
        }

        return back()->with('success', 'Competition results finalized.');
    }

    private function authorizeManagerOrAssignedJudge(Request $request, Event $event): void
    {
        abort_unless($request->user()->hasRole(['admin', 'staff']) || DB::table('judge_assignments')->where('event_id', $event->id)->where('user_id', $request->user()->id)->exists(), 403);
    }
}
