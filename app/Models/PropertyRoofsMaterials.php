<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class PropertyRoofsMaterials extends Model
{
    use LogsActivity;

    protected $fillable = [
        'label', 'value', 'is_active'
    ];

    protected $table  = 'property_roofs_materials';
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-roofs-materials';

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-roofs-materials') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
