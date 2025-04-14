<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyUse extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active'
    ];


    protected $table = 'property_use';
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-use';
}
