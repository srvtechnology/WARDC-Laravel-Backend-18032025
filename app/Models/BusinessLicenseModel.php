<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;

class BusinessLicenseModel extends Model
{

    protected $table = 'business_license';

    public function business()
    {
        return $this->belongsTo('App\Models\Business', 'business_id');
    }

}
