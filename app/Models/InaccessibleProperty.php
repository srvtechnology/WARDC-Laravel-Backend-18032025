<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
// use Folklore\Image\Facades\Image;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InaccessibleProperty extends Model
{
   
    use LogsActivity;

    const INACCESSBILE_PROPERTY_IMAGE = 'property/inaccessibles/image';


    protected $fillable = [
        'reason', 
        'inaccessbile_property_image',
        'inaccessbile_property_lat',
        'inaccessbile_property_long',
        'enumerator'
    ];

    
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'inaccessbile-property-details';

    protected $hidden = [
        'pivot'
    ];



    protected $appends = [
        'inaccessbile_property_image_path',
    ];


    //inaccessbile images
    public function getInaccessbileImagePathAttribute()
    {
        return $this->getInaccessbileImagePath(800, 800);
    }
    public function getInaccessibleImage()
    {
        return Storage::disk('s3')->url($this->inaccessbile_property_image);
    }
    public function hasInaccessbileImage()
    {
        return (bool) $this->inaccessbile_property_image && Storage::disk('s3')->exists($this->inaccessbile_property_image);
    }
    public function getInaccessbileImagePath($width = 800, $height = 800)
    {
        return $this->hasInaccessbileImage() ? Storage::disk('s3')->url($this->inaccessbile_property_image) : null;   
    }

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('inaccessbile-property-details') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }
}

