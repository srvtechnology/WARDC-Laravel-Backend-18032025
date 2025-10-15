<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AdminUser extends Authenticatable
{
    use HasApiTokens, LogsActivity;

    protected $table = 'admin_users';

    protected $fillable = [
        'first_name', 'email', 'password','last_name','image'
    ];

    protected $hidden = [
        'password',
    ];

    public function payments()
    {
        return $this->hasMany('App\Models\PropertyPayment','id','admin_user_id');
    }

    public function role_details()
    {
        return $this->hasOne('App\Models\RoleModel','id','role_id');
    }

    public function role()
    {
        return $this->belongsTo(\App\Models\RoleModel::class, 'role_id');
    }

    /**
     * Configure Spatie Activitylog options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('system-user') // custom log name
            ->logAll()                 // log all fillable attributes
            ->logOnlyDirty();          // log only changed values
    }
}
