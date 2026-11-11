<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['event_id', 'name', 'leader_student_id'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function members()
    {
        return $this->hasMany(Registration::class);
    }

    public function leader()
    {
        return $this->belongsTo(Student::class, 'leader_student_id');
    }
}
