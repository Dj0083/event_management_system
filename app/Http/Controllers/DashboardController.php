<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $visibleEvents = Event::query();
        $stats = [
            'events' => (clone $visibleEvents)->count(),
            'upcoming' => (clone $visibleEvents)->whereDate('event_date', '>=', today())->whereIn('status', ['published', 'scheduled'])->count(),
            'registrations' => DB::table('registrations')->count(),
            'pending' => DB::table('venue_bookings')->where('status', 'pending')->count() + DB::table('leave_requests')->where('status', 'pending')->count() + DB::table('registrations')->where('status', 'pending')->count(),
        ];
        $events = (clone $visibleEvents)->withCount('registrations')->whereDate('event_date', '>=', today())
            ->orderBy('event_date')->limit(5)->get();

        return view('dashboard', compact('stats', 'events'));
    }
}
