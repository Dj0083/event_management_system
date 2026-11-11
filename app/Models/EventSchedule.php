<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    protected $fillable = ['event_id', 'title', 'description', 'schedule_date', 'start_time', 'end_time', 'location'];

    protected function casts(): array
    {
        return ['schedule_date' => 'date'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
