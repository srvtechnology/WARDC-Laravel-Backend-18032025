<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class UserVerification extends Authenticatable
{
    use Notifiable, HasApiTokens;
    protected $fillable = ['mobile', 'code', 'expired_at'];
    public $timestamps = true;

    protected $dates = [
        'expired_at'
    ];

    public function isActive()
    {
        return $this->is_active;
    }

    public function getPhoneNumberAttribute()
    {
        return $this->mobile;
    }
}
