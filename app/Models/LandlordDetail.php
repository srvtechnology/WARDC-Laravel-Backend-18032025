<?php

namespace App\Models;

use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
// use Spatie\Activitylog\Traits\LogsActivity;

class LandlordDetail extends Model
{
    use Notifiable;
    // use LogsActivity;
    const DOCUMENT_IMAGE = 'property/landownerdocuments/image';
    //
    protected $fillable = [
        'ownerTitle',
        'first_name', 
        'middle_name', 
        'surname', 
        'email', 
        'sex',
        'street_number', 
        'street_numbernew',
        'street_name', 
        'image', 
        'id_number', 
        'id_type', 
        'tin', 
        'ward', 
        'constituency', 
        'section', 
        'chiefdom', 
        'district', 
        'province', 
        'postcode', 
        'mobile_1', 
        'mobile_2',
        'temp_first_name',
        'temp_middle_name',
        'temp_surname',
        'temp_street_number',
        'temp_street_name',
        'temp_email',
        'temp_mobile_1',
        'verified',
        'document_image',
        'address_image',
        'requested_by',
        'conveyance_image',
        'temp_street_numbernew'
    ];

    //protected static $ignoreChangedAttributes = ['first_name','updated_at'];
    protected static $logAttributesToIgnore = ['image','id_number','id_type','tin'];
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-landlord';

    protected $appends = ['original', 'small_preview', 'large_preview', 'phone_number', 'document_image_path','address_image_path','conveyance_image_path'];

    public function property()
    {
        return $this->belongsTo('App\Models\Property');
    }

    public function canReceiveAlphanumericSender()
    {
        return true;
    }

    public function titles()
    {
        return $this->hasOne(
            UserTitleTypes::class,
            'id',
            'ownerTitle'
        );
    }

    public function getName()
    {
        return $this->first_name . ' ' . $this->middle_name . ' ' . $this->surname;
    }

    public function getPhoneNumberAttribute()
    {
        return $this->mobile_1;
    }

    public function getSmallPreviewAttribute()
    {
        return $this->getImageUrl(100, 100);
    }

    public function getLargePreviewAttribute()
    {
        return $this->getImageUrl(800, 800);
    }

    public function getOriginalAttribute()
    {
        return $this->hasImage() ? url(Image::url($this->image)) :  null;
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
        return $this->hasImage() ? url(Image::url($this->image, $width, $height, ['crop'])) :  null;
    }

    public function boundryDelimetation()
    {
        return $this->belongsTo(BoundaryDelimitation::class, 'ward', 'ward');
    }


    //landowner verification documents

    public function getDocumentImagePathAttribute()
    {
        return $this->getDocumentImagePath(800, 800);
    }


    public function getDocumentImage()
    {
        return storage_path('app/' . $this->document_image);
    }

    public function hasDocumentImage()
    {
        return (bool) $this->document_image && file_exists($this->getDocumentImage());
    }

    public function getDocumentImagePath($width = 800, $height = 800)
    {
        return $this->hasDocumentImage() ? url(Image::url($this->document_image, $width, $height, [])) : null;   
    }






    public function getAddressImagePathAttribute()
    {
        return $this->getAddressImagePath(800, 800);
    }


    public function getAddressImage()
    {
        return storage_path('app/' . $this->address_image);
    }

    public function hasAddressImage()
    {
        return (bool) $this->address_image && file_exists($this->getAddressImage());
    }

    public function getAddressImagePath($width = 800, $height = 800)
    {
        return $this->hasAddressImage() ? url(Image::url($this->address_image, $width, $height, [])) : null;   
    }




    public function getConveyanceImagePathAttribute()
    {
        return $this->getConveyanceImagePath(800, 800);
    }


    public function getConveyanceImage()
    {
        return storage_path('app/' . $this->conveyance_image);
    }

    public function hasConveyanceImage()
    {
        return (bool) $this->conveyance_image && file_exists($this->getConveyanceImage());
    }

    public function getConveyanceImagePath($width = 800, $height = 800)
    {
        return $this->hasConveyanceImage() ? url(Image::url($this->conveyance_image, $width, $height, [])) : null;   
    }
}

