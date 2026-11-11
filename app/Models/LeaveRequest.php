<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['user_id', 'applicant_name', 'applicant_email', 'event_id', 'start_date', 'end_date', 'reason', 'document_path', 'status', 'reviewed_by', 'review_note'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
