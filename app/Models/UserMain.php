<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMain extends Model
{
    //
    protected $table = "users";
    protected $fillable = [
        'name', 'image', 'email', 'password', 'ward', 'constituency', 'section', 'chiefdom', 'district', 'province', 'street_name', 'street_number', 'gender', 'is_active', 'assign_district', 'assign_district_id', //'device_id'
    ];
    const USER_IMAGE = 'user/profile/image';

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function properties()
    {
        return $this->hasMany('App\Models\Property','user_id','id');
    }
}
