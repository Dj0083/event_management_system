<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeminarSession extends Model
{
    protected $fillable = ['seminar_id', 'lecturer_id', 'session_date', 'start_time', 'end_time', 'location'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function seminar()
    {
        return $this->belongsTo(Seminar::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }
}
