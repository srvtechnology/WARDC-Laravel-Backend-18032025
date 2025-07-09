<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admin_users';

    protected $fillable = [
        'name', 'email', 'password'
    ];

    protected $hidden = [
        'password',
    ];

    public function payments()
    {
        return $this->hasMany('App\Models\PropertyPayment','id','admin_user_id');
    }
}

