<?php

namespace App\Models;

use Folklore\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class PropertyGeoRegistry extends Model
{
    //
    // use LogsActivity;
    protected $fillable = [
        'meter_number', 'property_types_id', 'meter_images', 'point1', 'point2', 'point3', 'point4', 'point5', 'point6', 'point7', 'point8', 'digital_address', 'dor_lat_long', 'old_digital_address', 'open_location_code'
    ];

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'property-geo-registry';

    protected $table = 'property_geo_registry';

    protected $appends = ['small_preview', 'large_preview'];

    public function getSmallPreviewAttribute()
    {
        return $this->getMeterImageUrl(100, 100);
    }

    public function getLargePreviewAttribute()
    {
        return $this->getMeterImageUrl(800, 800);
    }

    public function property()
    {
        return $this->belongsTo('App\Models\Property');
    }

    public function hasMeterImage()
    {
        return $this->meter_images && file_exists($this->geMetertImage());
    }

    public function geMetertImage()
    {
        return storage_path('app/' . $this->meter_images);
    }

    public function getMeterImageUrl($width = 100, $height = 100)
    {
        return $this->hasMeterImage() ? url(Image::url(($this->meter_images), $width, $height, ['crop'])) : null;
    }

    public function getDigitalAddress()
    {
        $array = explode('-', $this->digital_address);

        if (count($array) < 2) {
            return implode('-', $array);
        }

        return implode('-', [
            $array[0],
            $array[1]
        ]);
    }

    public function getPoints($center = false, $arr = false)
    {
        if ($this->dor_lat_long) {
            $point = explode(', ', $this->dor_lat_long);

            if (count($point) == 2) {
                $points[] = [$this->property->getAddress(), $point[0], $point[1], 0];

                return json_encode($points);
            } else {
                return json_encode([]);
            }
        } else {
            return json_encode([]);
        }


        $points = [];

        for ($i = 1; $i <= 8; $i++) {
            $point = 'point' . $i;

            if ($this->$point) {
                $pArray = explode(',', $this->$point);

                if (count($pArray) == 2) {
                    if ($arr) {
                        $points[] = [trim($pArray[0]), trim($pArray[1])];
                    } else {

                        $points[] = ["lat" => trim(trim($pArray[0])), "lng" => trim($pArray[1])];
                    }
                }


                if ($center) {
                    return json_encode(array_filter(["lat" => trim($pArray[0]), "lng" => trim($pArray[1])]), JSON_NUMERIC_CHECK);
                }
            }
        }

        return $arr ? array_filter($points) : json_encode(array_filter($points), JSON_NUMERIC_CHECK);
    }

    public function getCenterPoint()
    {
        return $this->dor_lat_long;
    }

    public function getCenterPointForArray($data)
    {
        return json_encode($this->GetCenterFromDegrees(array_filter($data)), JSON_NUMERIC_CHECK);
    }

    function GetCenterFromDegrees(array $data)
    {
        if (!count($data)) {
            return false;
        }

        $numCoords = count($data);

        $X = 0.0;
        $Y = 0.0;
        $Z = 0.0;

        for ($i = 0; $i < count($data); $i++) {
            $lat = $data[$i][0] * pi() / 180;
            $lon = $data[$i][1] * pi() / 180;

            $a = cos($lat) * cos($lon);
            $b = cos($lat) * sin($lon);
            $c = sin($lat);

            $X += $a;
            $Y += $b;
            $Z += $c;
        }

        $X /= $numCoords;
        $Y /= $numCoords;
        $Z /= $numCoords;

        $lon = atan2($Y, $X);
        $hyp = sqrt($X * $X + $Y * $Y);
        $lat = atan2($Z, $hyp);

        $newX = ($lat * 180 / pi());
        $newY = ($lon * 180 / pi());

        return ['lat' => $newX, 'lng' => $newY];
    }

    public function getAllPoints()
    {
        $points = [];

        for ($i = 1; $i <= 8; $i++) {
            $point = 'point' . $i;

            if ($this->$point) {
                $pArray = explode(',', $this->$point);

                if (count($pArray) == 2) {


                    $points[] = ["lat" => trim(trim($pArray[0])), "lng" => trim($pArray[1])];
                }
            }
        }

        return $points;
    }
}
