<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = ['event_id', 'student_id', 'team_id', 'status', 'registered_at'];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
