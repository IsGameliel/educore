<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuitionTemplate extends Model
{
    use \App\Models\Concerns\BelongsToInstitution;

    protected $guarded = ['id'];
    protected $casts = ['items'=>'array', 'amount'=>'integer', 'first_percent'=>'integer'];
}
