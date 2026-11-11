<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Venue;
use App\Models\VenueBooking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Notifications\WorkflowNotice;

class VenueController extends Controller
{
    public function index()
    {
        $isManager = auth()->user()->hasRole(['admin', 'staff']);
        $bookings = VenueBooking::with(['venue', 'event'])->when(! $isManager, fn ($query) => $query->where('requested_by', auth()->id()))->latest()->paginate(12);
        return view('venues.index', [
            'venues' => Venue::orderBy('name')->get(),
            'availableVenues' => Venue::where('is_available', true)->orderBy('name')->get(),
            'bookings' => $bookings,
            'events' => Event::orderBy('name')->get(),
            'isManager' => $isManager,
        ]);
    }

    public function storeVenue(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:venues,name'],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(Venue::whereKey($data['venue_id'])->where('is_available', true)->exists(), 422, 'This venue is currently unavailable.');
        Venue::create($data);

        return back()->with('success', 'Venue added.');
    }

    public function availability(Request $request, Venue $venue)
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);
        $venue->update($data);

        return back()->with('success', 'Venue availability updated.');
    }

    public function storeBooking(Request $request)
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:255'],
        ]);

        $conflict = VenueBooking::where('venue_id', $data['venue_id'])
            ->whereDate('booking_date', $data['booking_date'])
            ->where('status', 'approved')
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])->exists();

        if ($conflict) {
            return back()->withErrors(['booking_date' => 'This venue already has an approved booking during that time.'])->withInput();
        }

        $data['requested_by'] = $request->user()->id;
        $booking = VenueBooking::create($data);
        User::whereIn('role', ['admin', 'staff'])->where('is_active', true)->get()->each(fn ($user) => $user->notify(new WorkflowNotice('Venue booking request', "A venue booking request was submitted for {$booking->booking_date->format('M j')}.", route('venues.index'))));

        return back()->with('success', 'Booking request submitted for approval.');
    }

    public function review(Request $request, VenueBooking $booking)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === 'approved') {
            abort_unless(Venue::whereKey($booking->venue_id)->where('is_available', true)->exists(), 422, 'This venue is currently unavailable.');
            $conflict = VenueBooking::where('venue_id', $booking->venue_id)
                ->whereDate('booking_date', $booking->booking_date)
                ->where('status', 'approved')->where('id', '!=', $booking->id)
                ->where('start_time', '<', $booking->end_time)->where('end_time', '>', $booking->start_time)->exists();
            if ($conflict) {
                return back()->withErrors(['booking' => 'Another approved booking overlaps this request.']);
            }
        }

        $booking->update($data + ['reviewed_by' => $request->user()->id]);
        $booking->loadMissing('requester');
        $booking->requester?->notify(new WorkflowNotice('Venue booking update', "Your booking request was {$data['status']}.", route('venues.index')));

        return back()->with('success', 'Booking request '.($data['status'] === 'approved' ? 'approved' : 'rejected').'.');
    }
}
