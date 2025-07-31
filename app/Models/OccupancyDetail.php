<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
// use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class OccupancyDetail extends Model
{
    use LogsActivity;
    protected $fillable = [
        'ownerTenantTitle','tenant_first_name', 'middle_name', 'surname', 'mobile_1', 'mobile_2'
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-occupancy-detail';

    // public function tapActivity(Activity $activity, string $eventName)
    // {
    //     \Mail::raw($activity, function ($message) {
    //         $message->to('kingshuk.mat@gmail.com')->subject('Your Test log');
    //     });        
    //     //exit("{$activity}.activity.logs.message.{$eventName}");
    // }

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-occupancy-detail') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

   public function property()
    {
        return $this->belongsTo('App\Models\Property');
    }
    
    
    public function titles()
    {
        return $this->hasOne(
            UserTitleTypes::class,
            'id',
            'ownerTenantTitle'
        );
    }
    

    protected $casts = [
        'owned_tenancy' => 'boolean',
        'rented' => 'boolean',
        'unoccupied_house' => 'boolean'
    ];
}
