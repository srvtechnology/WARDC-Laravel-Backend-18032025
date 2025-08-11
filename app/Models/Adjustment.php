<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Adjustment extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name'
    ];

    protected $table = 'counsil_adjustment_group_a';

    // protected static $logAttributes = ['*'];
    // protected static $logOnlyDirty = true;
    // protected static $logName = 'swimmings';

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('counsil_adjustment_group_a') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
