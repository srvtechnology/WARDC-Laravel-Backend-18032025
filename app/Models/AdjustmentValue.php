<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;

class AdjustmentValue extends Model
{
    //use LogsActivity;
    protected $fillable = [
        'adjustment_id','group_name','percentage'
    ];

    protected $table = 'adjustment_values';

    // protected static $logAttributes = ['*'];
    // protected static $logOnlyDirty = true;
    // protected static $logName = 'swimmings';


    public function adjustment()
    {
        return $this->belongsTo(Adjustment::class);
    }

}
