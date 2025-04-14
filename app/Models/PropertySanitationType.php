<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class PropertySanitationType extends Model
{
    use LogsActivity;
    protected $fillable = [
        'label', 'value',
    ];

    protected $table = 'property_sanitation_types';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-sanitation-types';

    protected $hidden = [
        'pivot'
    ];

}
