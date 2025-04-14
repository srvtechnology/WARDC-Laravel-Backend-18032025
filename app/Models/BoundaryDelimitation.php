<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class BoundaryDelimitation extends Model
{
    // use LogsActivity;
    protected static $logAttributes = ['*'];
    public $timestamps = false;
    protected $fillable = [
        'ward', 'constituency', 'section', 'chiefdom', 'district', 'province', 'council', 'prefix'
    ];
}
