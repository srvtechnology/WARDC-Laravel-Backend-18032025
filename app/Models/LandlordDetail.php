<?php

namespace App\Models;

use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class LandlordDetail extends Model
{
    use Notifiable;
    use LogsActivity;
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

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-landlord') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

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
        return $this->image && Storage::disk('s3')->exists($this->image);
    }

    public function getImage()
    {
        return Storage::disk('s3')->url($this->image);
    }

    public function getImageUrl($width = 100, $height = 100)
    {
        return $this->hasImage() ? Storage::disk('s3')->url($this->image) :  null;
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
        return Storage::disk('s3')->url($this->document_image);
    }

    public function hasDocumentImage()
    {
        return (bool) $this->document_image && Storage::disk('s3')->exists($this->document_image);
    }

    public function getDocumentImagePath($width = 800, $height = 800)
    {
        return $this->hasDocumentImage() ? Storage::disk('s3')->url($this->document_image) : null;   
    }






    public function getAddressImagePathAttribute()
    {
        return $this->getAddressImagePath(800, 800);
    }


    public function getAddressImage()
    {
        return Storage::disk('s3')->url($this->address_image);
    }

    public function hasAddressImage()
    {
        return (bool) $this->address_image && Storage::disk('s3')->exists($this->address_image);
    }

    public function getAddressImagePath($width = 800, $height = 800)
    {
        return $this->hasAddressImage() ? Storage::disk('s3')->url($this->address_image) : null;   
    }




    public function getConveyanceImagePathAttribute()
    {
        return $this->getConveyanceImagePath(800, 800);
    }


    public function getConveyanceImage()
    {
        return Storage::disk('s3')->url($this->conveyance_image);
    }

    public function hasConveyanceImage()
    {
        return (bool) $this->conveyance_image && Storage::disk('s3')->exists($this->conveyance_image);
    }

    public function getConveyanceImagePath($width = 800, $height = 800)
    {
        return $this->hasConveyanceImage() ? Storage::disk('s3')->url($this->conveyance_image) : null;   
    }
}

