<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class UserMain extends Authenticatable
{
    use HasApiTokens, Notifiable, LogsActivity;

    protected $table = "users";

    protected $fillable = [
        'name', 'image', 'email', 'password', 'ward', 'constituency', 'section',
        'chiefdom', 'district', 'province', 'street_name', 'street_number',
        'gender', 'is_active', 'assign_district', 'assign_district_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    public function properties()
    {
        return $this->hasMany(Property::class, 'user_id', 'id');
    }

    /**
     * Configure Spatie Activitylog options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('assesment-app-users') // custom log name
            ->logOnly([
                'name',
                'email',
                'ward',
                'constituency',
                'district',
                'province',
                'is_active'
            ]) // only log these fields
            ->logOnlyDirty() // only log changes
            ->dontSubmitEmptyLogs(); // avoid empty logs
    }
}
