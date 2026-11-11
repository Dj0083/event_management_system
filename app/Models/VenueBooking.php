<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueBooking extends Model
{
    protected $fillable = ['venue_id', 'event_id', 'requested_by', 'booking_date', 'start_time', 'end_time', 'purpose', 'status', 'reviewed_by', 'review_note'];

    protected function casts(): array
    {
        return ['booking_date' => 'date'];
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
