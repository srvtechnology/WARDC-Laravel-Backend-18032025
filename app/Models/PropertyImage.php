<?php

namespace App\Models;

use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    protected $fillable = [
        'image',
        'type'
    ];

    protected $appends = ['small_preview', 'large_preview'];

    public function getSmallPreviewAttribute()
    {
        return $this->getImageUrl(100, 100);
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
        return $this->hasImage() ? url(Image::url($this->image, $width, $height, ['crop'])) : null;
    }
}
