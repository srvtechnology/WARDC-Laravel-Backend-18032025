<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyWallMaterials extends Model
{
    //
    // use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active'
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    protected $table = 'property_wall_materials';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-wall-materials';
}
