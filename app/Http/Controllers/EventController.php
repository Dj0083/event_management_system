<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::with(['category'])->withCount('registrations')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('event_date')->paginate(12)->withQueryString();

        return view('events.index', ['events' => $events, 'categories' => EventCategory::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('events.create', ['event' => new Event(), 'categories' => EventCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['created_by'] = $request->user()->id;
        $data['image_path'] = $request->file('image')->store('events', 'public');
        unset($data['image']);
        $event = Event::create($data);

        return redirect()->route('events.show', $event)->with('success', 'Event created.');
    }

    public function show(Event $event)
    {
        $event->loadCount('registrations')->load('schedules');

        return view('events.show', compact('event'));
    }

    public function edit(Event $event)
    {
        return view('events.create', ['event' => $event, 'categories' => EventCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('events', 'public');
            if ($event->image_path) {
                Storage::disk('public')->delete($event->image_path);
            }
            $data['image_path'] = $newImagePath;
        }
        unset($data['image']);
        $event->update($data);

        return redirect()->route('events.show', $event)->with('success', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }

    private function validated(Request $request, bool $imageRequired = false): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:event_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['competition', 'seminar', 'workshop', 'other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled', 'completed', 'cancelled'])],
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:event_categories,name']]);
        $baseSlug = Str::slug($data['name']) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;
        while (EventCategory::where('slug', $slug)->exists()) $slug = $baseSlug.'-'.($suffix++);
        EventCategory::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('success', 'Event category added.');
    }
}
