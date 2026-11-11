<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = ['event_id', 'registration_id', 'total_score', 'rank', 'status', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['total_score' => 'decimal:2', 'finalized_at' => 'datetime'];
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
