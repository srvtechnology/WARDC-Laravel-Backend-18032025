<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class UserMain extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = "users";

    protected $fillable = [
        'name', 'image', 'email', 'password', 'ward', 'constituency', 'section',
        'chiefdom', 'district', 'province', 'street_name', 'street_number',
        'gender', 'is_active', 'assign_district', 'assign_district_id' //'device_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    public function properties()
    {
        return $this->hasMany('App\Models\Property','user_id','id');
    }
}
