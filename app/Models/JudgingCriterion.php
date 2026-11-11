<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JudgingCriterion extends Model
{
    protected $fillable = ['event_id', 'name', 'description', 'max_score', 'weight'];

    protected function casts(): array
    {
        return ['max_score' => 'decimal:2', 'weight' => 'decimal:2'];
    }
}
