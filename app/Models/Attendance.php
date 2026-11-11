<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = ['registration_id', 'recorded_by', 'checked_in_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
