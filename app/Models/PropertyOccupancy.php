<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyOccupancy extends Model
{
    // use LogsActivity;
    protected $fillable = ['occupancy_type'];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-occupancy';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
