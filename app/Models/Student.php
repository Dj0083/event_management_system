<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['user_id', 'department_id', 'student_number', 'name', 'email', 'academic_program', 'year_of_study', 'phone'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
