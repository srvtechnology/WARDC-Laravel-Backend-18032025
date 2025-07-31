<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class PropertyType extends Model
{
    use LogsActivity;
    protected $fillable = [
        'label', 'value',
    ];

    protected $table = 'property_types';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-types';

    protected $hidden = [
        'pivot'
    ];

    public function assessment()
    {
        return $this->hasMany('App\Models\Property', 'property_types_id');
    }

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_property_type');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-types') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
