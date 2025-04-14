<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Spatie\Activitylog\Traits\LogsActivity;

class PropertyToCounsilGroupA extends Model
{
  
    protected $table = 'property_to_counsil_adjustment_group_a';


     public function counsilAdjustmentsGroupA(){
        return $this->hasMany('App\Models\CounsilAdjustmentGroupA', 'id', 'adjustment_id');
    }

}
