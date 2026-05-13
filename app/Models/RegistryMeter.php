<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class RegistryMeter extends Model
{
    use LogsActivity;
    protected $fillable = [
        'number',
        'image'
    ];

    protected static $logAttributes = ['*'];
    //protected static $logOnlyDirty = true;
    protected static $logName = 'property-registry-meter';

    protected $appends = ['original', 'small_preview', 'large_preview'];

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-registry-meter') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

    public function getSmallPreviewAttribute()
    {
        return $this->getImageUrl(100, 100);
    }
    public function getOriginalAttribute()
    {
        return $this->hasImage() ? Storage::disk('s3')->url($this->image) : asset('/images/No_Image_Available.jpg');
    }

    public function getLargePreviewAttribute()
    {
        return $this->getImageUrl(800, 800);
    }

    public function hasImage()
    {
        return $this->image && Storage::disk('s3')->exists($this->image);
    }

    public function getImage()
    {
        return Storage::disk('s3')->url($this->image);
    }

    public function getImageUrl($width = 100, $height = 100)
    {
        return $this->hasImage() ? Storage::disk('s3')->url($this->image) : asset('/images/No_Image_Available.jpg');
    }

    public function property()
    {
        return $this->belongsTo('App\Models\Property');
    }
}
