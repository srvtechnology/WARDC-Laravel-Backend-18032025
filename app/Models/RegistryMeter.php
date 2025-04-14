<?php

namespace App\Models;

use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class RegistryMeter extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'number',
        'image'
    ];

    protected static $logAttributes = ['*'];
    //protected static $logOnlyDirty = true;
    protected static $logName = 'property-registry-meter';

    protected $appends = ['original', 'small_preview', 'large_preview'];

    public function getSmallPreviewAttribute()
    {
        return $this->getImageUrl(100, 100);
    }
    public function getOriginalAttribute()
    {
        return $this->hasImage() ? url(Image::url($this->image)) : url(asset('/images/No_Image_Available.jpg'), 100, 100, ['crop']);
    }

    public function getLargePreviewAttribute()
    {
        return $this->getImageUrl(800, 800);
    }

    public function hasImage()
    {
        return $this->image && file_exists($this->getImage());
    }

    public function getImage()
    {
        return storage_path('app/' . $this->image);
    }

    public function getImageUrl($width = 100, $height = 100)
    {
        return $this->hasImage() ? url(Image::url($this->image, $width, $height, ['crop'])) : url(asset('/images/No_Image_Available.jpg'), $width, $height, ['crop']);
    }

    public function property()
    {
        return $this->belongsTo('App\Models\Property');
    }
}
