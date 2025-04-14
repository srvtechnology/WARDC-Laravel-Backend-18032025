<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
// use Spatie\Activitylog\Models\Activity;

class PropertyCategory extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active',
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-categories';

    protected $table = 'property_categories';

    // public function tapActivity(Activity $activity, string $eventName)
    // {
    //     \Mail::raw($activity, function ($message) {
    //         $message->to('kingshuk.mat@gmail.com')->subject('Your Test log');
    //     });        
    //     //exit("{$activity}.activity.logs.message.{$eventName}");
    // }

    public function assessments()
    {
        return $this->hasMany('App\Models\Property', 'property_categories_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Property::class);
    }
}
