<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class BoundaryDelimitation extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['*'];
    public $timestamps = false;
    protected $fillable = [
        'ward', 'constituency', 'section', 'chiefdom', 'district', 'province', 'council', 'prefix'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('boundary-delimitation') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
