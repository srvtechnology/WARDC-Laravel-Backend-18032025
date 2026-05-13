<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
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
        return $this->image && Storage::disk('s3')->exists($this->image);
    }

    public function getImage()
    {
        return Storage::disk('s3')->url($this->image);
    }

    public function getImageUrl($width = 100, $height = 100)
    {
        return $this->hasImage() ? Storage::disk('s3')->url($this->image) : null;
    }
}
