<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seminar extends Model
{
    protected $fillable = ['event_id', 'topic', 'description'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function sessions()
    {
        return $this->hasMany(SeminarSession::class);
    }
}
