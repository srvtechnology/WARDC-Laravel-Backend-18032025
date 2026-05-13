<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
// use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class PropertyPayment extends Model
{
    use SoftDeletes;
    use LogsActivity;
    const PHYSICAL_RECEIPT_IMAGE = 'property/physical_receipt/image';
    const PENSIONER_DISCOUNT_IMAGE = 'property/pensioner_discount/image';
    const DISABILITY_DISCOUNT_IMAGE = 'property/disability_discount/image';
    
    protected $fillable = [
        'assessment',
        'amount',
        'payment_type',
        'cheque_number',
        'payee_name',
        'payment_id',
        'admin_user_id',
        'balance',
        'penalty',
        'total',
        'migrate_at',
        'physical_receipt_image',
        'pensioner_discount_image',
        'disability_discount_image',
        'pensioner_discount_approve',
        'disability_discount_approve',
        'payment_made_year',
        'discount_offered',
        'transaction_id',
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-payment';

    public function getActivitylogOptions(): LogOptions
    {
        // dd(\Auth::guard('sanctum')->user()->id);
        return LogOptions::defaults()
            ->useLogName('property-payment') // custom log name
            ->logAll()                          // Log all attributes
            ->logOnlyDirty();                   // Only log changed values
    }

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id')->withDefault([
            'id' => 1
        ]);
    }

    public static function installmentDates($key = null)
    {
        $dates = [
            '0' => '31-03-2019',
            '1' => '30-06-2019',
            '2' => '30-09-2019',
            '3' => '31-12-2019',
        ];

        return (isset($dates[$key])) ? $dates[$key] : $dates;
    }

    public static function numberToWord($number = null)
    {
        $words = [
            '1' => 'First',
            '2' => 'Second',
            '3' => 'Third',
            '4' => 'Fourth'
        ];

        return (isset($words[$number])) ? $words[$number] : $words;
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function totalAssessment()
    {
        return number_format($this->assessment);
    }

    public function amountPaid()
    {
        return number_format($this->amount);
    }

    public function dueAmount()
    {
        return number_format($this->balance + $this->amount);
    }

    public function getBalance()
    {
        return number_format($this->balance);
    }

    protected $appends = [
        'physical_receipt_image_path',
        'pensioner_discount_image_path',
        'disability_discount_image_path'
    ];


    //physical receipt image
    public function getPhysicalReceiptImagePathAttribute()
    {
        return $this->getPhysicalReceiptImagePath(800, 800);
    }

    public function getPhysicalReceiptImage()
    {
        return Storage::disk('s3')->url($this->physical_receipt_image);
    }

    public function hasPhysicalReceiptImage()
    {
        return (bool) $this->physical_receipt_image && Storage::disk('s3')->exists($this->physical_receipt_image);
    }

    public function getPhysicalReceiptImagePath($width = 100, $height = 100)
    {
        return $this->hasPhysicalReceiptImage() ? Storage::disk('s3')->url($this->physical_receipt_image) : null;   
    }




    //pensioner_discount_images
    public function getPensionerDiscountImagePathAttribute()
    {
        return $this->getPensionerDiscountImagePath(800, 800);
    }

    public function getPensionerDiscountImage()
    {
        return Storage::disk('s3')->url($this->pensioner_discount_image);
    }

    public function hasPensionerDiscountImage()
    {
        return (bool) $this->pensioner_discount_image && Storage::disk('s3')->exists($this->pensioner_discount_image);
    }

    public function getPensionerDiscountImagePath($width = 100, $height = 100)
    {
        return $this->hasPensionerDiscountImage() ? Storage::disk('s3')->url($this->pensioner_discount_image) : null;   
    }


    //disability discount image
    public function getDisabilityDiscountImagePathAttribute()
    {
        return $this->getDisabilityDiscountImagePath(800, 800);
    }

    public function getDisabilityDiscountImage()
    {
        return Storage::disk('s3')->url($this->disability_discount_image);
    }

    public function hasDisabilityDiscountImage()
    {
        return (bool) $this->disability_discount_image && Storage::disk('s3')->exists($this->disability_discount_image);
    }

    public function getDisabilityDiscountImagePath($width = 100, $height = 100)
    {
        return $this->hasDisabilityDiscountImage() ? Storage::disk('s3')->url($this->disability_discount_image) : null;   
    }

}

