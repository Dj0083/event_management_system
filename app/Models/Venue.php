<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = ['name', 'type', 'capacity', 'location', 'description', 'is_available'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function bookings()
    {
        return $this->hasMany(VenueBooking::class);
    }
}
