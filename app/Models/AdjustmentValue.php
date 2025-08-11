<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class AdjustmentValue extends Model
{
    use LogsActivity;
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

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('adjustment-values') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

}
