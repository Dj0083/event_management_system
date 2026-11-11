<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshmentDistribution extends Model
{
    protected $fillable = ['refreshment_plan_id', 'registration_id', 'quantity', 'distributed_at'];

    protected function casts(): array
    {
        return ['distributed_at' => 'datetime'];
    }
}
