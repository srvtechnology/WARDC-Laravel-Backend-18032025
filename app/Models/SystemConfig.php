<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SystemConfig extends Model
{
    use LogsActivity;
    protected $fillable = ['option_name', 'option_value'];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'system-config';
}
