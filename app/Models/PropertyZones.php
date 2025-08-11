<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class PropertyZones extends Model
{
    use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active'
    ];

    protected $table = 'property_zones';
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-zones';

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-zones') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
