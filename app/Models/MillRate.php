<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;

class MillRate extends Model
{
    //use LogsActivity;
    protected $fillable = [
        'group_name','rate'
    ];

    protected $table = 'mill_rates';


}
