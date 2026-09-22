<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
    protected $fillable = [
        'user_id',
        'application_number',
        'status',
        'admission_type',
        'surname',
        'first_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'parent_name',
        'parent_relationship',
        'parent_phone',
        'parent_email',
        'parent_address',
        'department_id',
        'level',
        'entry_year',
        'previous_school',
        'qualification',
        'olevel_results',
        'graduation_year',
        'academic_notes',
        'jamb_result_path',
        'diploma_result_path',
        'transcript_path',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'olevel_results' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
