<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;

class PropertyCharacteristicValue extends Model
{
    //use LogsActivity;
    protected $fillable = [
        'property_characteristic_id','group_name','good', 'average', 'bad'
    ];

    protected $table = 'property_characteristic_values';

    // protected static $logAttributes = ['*'];
    // protected static $logOnlyDirty = true;
    // protected static $logName = 'swimmings';


    public function propertyCharacteristic()
    {
        return $this->belongsTo(PropertyCharacteristic::class);
    }

}
