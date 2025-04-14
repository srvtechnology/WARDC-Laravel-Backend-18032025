<?php


namespace App\Models;


use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    const IMAGE_PATH = 'District';
    protected $fillable = [
        'name', 'council_name', 'council_short_name', 'council_address', 'penalties_note', 'warning_note', 'collection_point', 'bank_details', 'primary_logo', 'secondary_logo', 'chif_administrator_sign', 'ceo_sign', 'enquiries_email', 'enquiries_phone', 'enquiries_phone2', 'feedback', 'sq_meter_value', 'name_envp', 'council_name_envp', 'council_short_name_envp', 'primary_logo_envp', 'secondary_logo_envp', 'council_address_envp'
    ];
    protected $casts = [
        'collection_point' => 'array',
        'collection_point2' => 'array',
        'bank_details' => 'array'
    ];
    /*Primary Logo*/
    public function hasPrimaryLogo()
    {
        return $this->primary_logo && file_exists($this->getPrimaryLogo());
    }
    public function getPrimaryLogo()
    {
        return storage_path('app/' . $this->primary_logo);
    }
    public function getPrimaryLogoUrl($width = 100, $height = 100, $resize = false)
    {
        //dd($this->council_logo);
        return $this->hasPrimaryLogo() ? url(Image::url($this->primary_logo, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }

    public function getPrimaryLogoEnvpUrl($width = 100, $height = 100, $resize = false)
    {
        //dd($this->council_logo);
        return $this->hasPrimaryLogoEnvp() ? url(Image::url($this->primary_logo_envp, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }    
    public function getPrimaryLogoEnvp()
    {
        return storage_path('app/' . $this->primary_logo_envp);
    }
    public function hasPrimaryLogoEnvp()
    {
        return $this->primary_logo_envp && file_exists($this->getPrimaryLogoEnvp());
    }    
    /*Secondary Logo*/
    public function hasSecondaryLogo()
    {
        return $this->secondary_logo && file_exists($this->getSecondaryLogo());
    }
    public function getSecondaryLogo()
    {
        return storage_path('app/' . $this->secondary_logo);
    }
    public function getSecondaryLogoUrl($width = 100, $height = 100, $resize = false)
    {
        return $this->hasSecondaryLogo() ? url(Image::url($this->secondary_logo, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }
    /*Secondary Logo end*/

    public function getSecondaryLogoEnvpUrl($width = 100, $height = 100, $resize = false)
    {
        //dd($this->council_logo);
        return $this->hasSecondaryLogoEnvp() ? url(Image::url($this->secondary_logo_envp, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }    
    public function getSecondaryLogoEnvp()
    {
        return storage_path('app/' . $this->secondary_logo_envp);
    }
    public function hasSecondaryLogoEnvp()
    {
        return $this->secondary_logo_envp && file_exists($this->getSecondaryLogoEnvp());
    } 

    /* chif administrator sign Start */
    public function hasChifAdministratorSign()
    {
        return $this->chif_administrator_sign && file_exists($this->getSecondaryLogo());
    }
    public function getChifAdministratorSign()
    {
        return storage_path('app/' . $this->chif_administrator_sign);
    }
    public function getChifAdministratorSignUrl($width = 100, $height = 100, $resize = false)
    {
        return $this->hasChifAdministratorSign() ? url(Image::url($this->chif_administrator_sign, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }
    /*Secondary Logo end*/

    /*'ceo_sign */
    public function hasCeoSign()
    {
        return $this->ceo_sign && file_exists($this->getCeoSign());
    }
    public function getCeoSign()
    {
        return storage_path('app/' . $this->ceo_sign);
    }
    public function getCeoSignUrl($width = 100, $height = 100, $resize = false)
    {
        return $this->hasCeoSign() ? url(Image::url($this->ceo_sign, $width, $height, $resize ? [] : ['crop'])) : url(Image::url("District/council_logo.jpg", $width, $height, ['crop']));
    }
    /*Secondary Logo end*/
}
