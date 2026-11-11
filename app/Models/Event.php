<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'category_id', 'created_by', 'name', 'type', 'description', 'image_path', 'event_date',
        'start_time', 'end_time', 'location', 'capacity', 'registration_opens_at',
        'registration_closes_at', 'status',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
}
