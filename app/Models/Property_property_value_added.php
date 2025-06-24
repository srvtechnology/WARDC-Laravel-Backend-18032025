<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property_property_value_added extends Model
{
   

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

}
