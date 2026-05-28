<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tests extends Model
{
    protected $fillable = ['name', 'subject', 'duration', 'status', 'level', 'department_id'];

    public function questions()
    {
        return $this->hasMany(Questions::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

}
