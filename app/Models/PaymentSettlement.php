<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSettlement extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['exceptions'=>'array', 'settled_at'=>'datetime', 'checked_at'=>'datetime'];
}
