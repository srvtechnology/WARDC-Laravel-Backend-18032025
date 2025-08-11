<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Property_property_value_added extends Model
{
   
    use LogsActivity;
    protected $table = 'property_property_value_added';

    public function property_details()
    {
        return $this->hasOne('App\Models\Property','id','property_id');
    }

    public function property_value_add_details()
    {
        return $this->hasOne('App\Models\PropertyValueAdded','id','property_value_added_id');
    }

    public function property_assesment_details()
    {
        return $this->hasOne('App\Models\PropertyAssessmentDetail','id','assessment_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property_property_value_added') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

}
