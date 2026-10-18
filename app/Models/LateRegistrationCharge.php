<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class LateRegistrationCharge extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['user_id', 'academic_session_id', 'semester', 'status'];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
