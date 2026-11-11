<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $fillable = ['user_id', 'department_id', 'name', 'email', 'phone', 'organization'];
}
