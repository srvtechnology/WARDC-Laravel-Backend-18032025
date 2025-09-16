<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class PropertyWallMaterials extends Model
{
    
    use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active','cat_id'
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    protected $table = 'property_wall_materials';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-wall-materials';

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-wall-materials') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
