<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = ['event_id', 'registration_id', 'name', 'place', 'cash_amount', 'distribution_status', 'distributed_at'];

    protected function casts(): array
    {
        return ['cash_amount' => 'decimal:2', 'distributed_at' => 'date'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
