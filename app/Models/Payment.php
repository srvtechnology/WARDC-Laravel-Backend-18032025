<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{


    public $incrementing = false;

    protected $fillable = [
        'property_id',
        'mobile_number',
        'payee_name',
        'payment_id',
        'payment_mode',
        'amount',
        'amount_in_le',
        'is_completed'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
