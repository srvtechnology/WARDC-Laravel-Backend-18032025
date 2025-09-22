<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CounsilAdjustmentGroupA extends Model
{
    use LogsActivity;

    protected $table = 'counsil_adjustment_group_a';

    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'type',
        'sign',
        'percentage',
        'category',
    ];

    /**
     * Configure the Spatie activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('council-adjustment') // Custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}
