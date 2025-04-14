<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyValueAdded extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'label', 'value', 'is_active'
    ];

    protected $table = 'property_value_added';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-value-added';

    protected $hidden = [
        'pivot'
    ];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_property_value_added');
    }
}
