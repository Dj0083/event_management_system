<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\RefreshmentDistribution;
use App\Models\RefreshmentItem;
use App\Models\RefreshmentPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefreshmentController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderByDesc('event_date')->get();
        $event = $events->firstWhere('id', $request->integer('event_id'));
        $plans = RefreshmentPlan::with(['item', 'event'])->when($event, fn ($query) => $query->where('event_id', $event->id))->withSum('distributions', 'quantity')->latest()->paginate(20)->withQueryString();

        return view('refreshments.index', ['events' => $events, 'event' => $event, 'plans' => $plans,
            'items' => RefreshmentItem::orderBy('name')->get(), 'registrations' => $event?->registrations()->with('student')->where('status', 'approved')->get() ?? collect()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'], 'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'], 'quantity' => ['required', 'integer', 'min:1'],
            'estimated_cost' => ['required', 'numeric', 'min:0'], 'status' => ['required', Rule::in(['planned', 'preparing', 'ready'])],
        ]);
        $item = RefreshmentItem::firstOrCreate(['name' => $data['item_name']], ['unit' => $data['unit']]);
        $existing = RefreshmentPlan::where('event_id', $data['event_id'])->where('refreshment_item_id', $item->id)->first();
        if ($existing && $data['quantity'] < $existing->distributions()->sum('quantity')) {
            return back()->withErrors(['quantity' => 'Planned quantity cannot be lower than the amount already distributed.'])->withInput();
        }
        RefreshmentPlan::updateOrCreate(['event_id' => $data['event_id'], 'refreshment_item_id' => $item->id], [
            'quantity' => $data['quantity'], 'estimated_cost' => $data['estimated_cost'], 'status' => $data['status'],
        ]);

        return back()->with('success', 'Refreshment plan saved.');
    }

    public function distribute(Request $request)
    {
        $data = $request->validate([
            'refreshment_plan_id' => ['required', 'exists:refreshment_plans,id'],
            'registration_id' => ['nullable', 'exists:registrations,id'], 'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $plan = RefreshmentPlan::with('event')->findOrFail($data['refreshment_plan_id']);
        if (! empty($data['registration_id'])) {
            abort_unless($plan->event->registrations()->whereKey($data['registration_id'])->where('status', 'approved')->exists(), 422, 'Select an approved participant for this event.');
        }
        $distributed = $plan->distributions()->sum('quantity');
        abort_if($distributed + $data['quantity'] > $plan->quantity, 422, 'That amount exceeds the planned quantity remaining.');
        RefreshmentDistribution::create($data + ['distributed_at' => now()]);

        return back()->with('success', 'Refreshment distribution recorded.');
    }
}
