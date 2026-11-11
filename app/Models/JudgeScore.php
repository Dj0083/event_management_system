<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JudgeScore extends Model
{
    protected $fillable = ['judging_criterion_id', 'judge_user_id', 'registration_id', 'score', 'comments'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }
}
