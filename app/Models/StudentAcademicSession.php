<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAcademicSession extends Model
{
    protected $fillable = ['user_id', 'academic_session_id', 'level'];
}
