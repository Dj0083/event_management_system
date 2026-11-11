<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshmentPlan extends Model
{
    protected $fillable = ['event_id', 'refreshment_item_id', 'quantity', 'estimated_cost', 'status'];

    protected function casts(): array
    {
        return ['estimated_cost' => 'decimal:2'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function item()
    {
        return $this->belongsTo(RefreshmentItem::class, 'refreshment_item_id');
    }

    public function distributions()
    {
        return $this->hasMany(RefreshmentDistribution::class);
    }
}
