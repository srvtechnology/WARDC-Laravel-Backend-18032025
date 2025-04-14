<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyWindowType extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'label', 'value',
    ];

    protected $table = 'property_window_types';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-window-types';

    protected $hidden = [
        'pivot'
    ];

}
