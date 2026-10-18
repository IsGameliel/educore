<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class StudentAcademicSession extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['user_id', 'academic_session_id', 'level'];
}
