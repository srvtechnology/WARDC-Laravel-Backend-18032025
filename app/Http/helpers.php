<?php

use Carbon\Carbon;
use App\BusinessLicense;
use App\Models\AdminUser;
use App\Models\Attribute;
use App\LicenseAmountHistory;
use App\UserAssignedBusiness;
use App\UserAssignedStreetApplication;
use App\Models\AuditModel;

if (!function_exists('audit_log')) {
    function audit_log($action, $category,$entity_id=null, $extraData = null)
    {
         $user = auth('sanctum')->user();
        $requestdatas = is_array($extraData) || is_object($extraData)
            ? json_encode($extraData)
            : (string) $extraData;

        $audit = new AuditModel();
        $audit->causer_id     = $user->id;
        $audit->causer_type   = get_class($user);
        $audit->log_name    = $category;
        $audit->description   = $action;

        $audit->properties = $requestdatas;
        $audit->subject_id = $entity_id;
        $audit->save();

    }
}


function encodePlusCode($latitude, $longitude, $codeLength = 10)
{
    $codeAlphabet = '23456789CFGHJMPQRVWX';
    $pairResolutions = [20.0, 1.0, .05, .0025, .000125];
    $gridRows = 5;
    $gridCols = 4;
    $separator = '+';
    $separatorPosition = 8;
    $paddingCharacter = '0';

    // Normalizing lat/lng
    $latitude = min(90.0, max(-90.0, $latitude));
    $longitude = fmod($longitude + 180.0, 360.0);
    if ($longitude < 0) $longitude += 360.0;

    $code = '';
    $lat = $latitude + 90.0;
    $lng = $longitude;

    // Pair encoding
    for ($i = 0; $i < ($codeLength < $separatorPosition ? $codeLength : $separatorPosition) / 2; $i++) {
        $latPlace = (int)($lat / $pairResolutions[$i]);
        $lngPlace = (int)($lng / $pairResolutions[$i]);

        $code .= $codeAlphabet[$latPlace];
        $code .= $codeAlphabet[$lngPlace];

        $lat -= $latPlace * $pairResolutions[$i];
        $lng -= $lngPlace * $pairResolutions[$i];
    }

    // Add separator
    $code = substr($code, 0, $separatorPosition) . $separator . substr($code, $separatorPosition);

    return $code;
}


 function abc()
    {
        return 'Permission Granted abc';
    }

function getQuarter($date, $endDate = null)
{
    if ($endDate === null) {
        $endDate = Carbon::now();
    } else if (!$endDate instanceof Carbon) {
        $endDate = Carbon::parse($endDate);
    }

    $from = Carbon::parse($date)->startOfYear();
    $to = $endDate->endOfMonth()->addDay();

    $diff = $from->diffInMonths($to);
    $subQuarter = intval(ceil($diff / 3));

    return $subQuarter;
}

function assessmentYearArray()
{
    $output = [];

    for ($i = date('Y'); $i >= 2019; $i--) {
        $output[$i] = $i;
    }

    return $output;
}

function getSyncArray($values, $columns)
{
    $output = [];
    if ($values) {
        foreach ($values as $value) {
            $output[$value] = $columns;
        }
    }

    return $output;
}

function getCategoryArray($categories, $parent_id = 0)
{
    $output = [];

    if ($categories) {
        foreach ($categories as $category) {

            if ($category['parent_id'] == $parent_id) {
                $children = getCategoryArray($categories, $category['id']);

                if ($children) {
                    $category['children'] = $children;
                }

                $output[] = $category;
            }
        }
    }

    return $output;
}

function createMenu($categories, $parent_id = 0, $html = '')
{

    $html .= '<ul>';
    foreach ($categories as $category) {

        if (isset($category['children'])) {

            $html .= '<li class="dropdown">
                            <a href="#" data-placement="bottom" type="link"  id="dropdownMenu" data-toggle="dropdown">' . $category['name'] . ' <i class="fa fa-caret-down"></i></a>';

            if (isset($category['children'])) {
                $html .= createSubMenu($category['children'], $category['slug']);
            }

            $html .= '</li>';
        } else {
            $html .= '<li>
                <a href="' . url($category['slug']) . '">' . $category['name'] . '</a>
            </li>';
        }
    }

    $html .= '</ul>';

    return $html;
}

function addMenuToTop($categories)
{
    //    $categories[] = ['name' => 'Eye Exam', 'slug' => '#', 'children' => [
    //       'name' => 'Schedule Care', 'slug' => '#'
    //    ]];

    $categories[] = ['name' => 'Home Try Ons', 'slug' => 'try-at-home'];
    $categories[] = ['name' => 'Offers', 'slug' => 'offers'];
    $categories[] = ['name' => 'Blog', 'slug' => 'blog',];
    $categories[] = ['name' => 'Help', 'slug' => 'help', 'parent_id' => null];

    return $categories;
}


function createSubMenu($category, $slug)
{
    $slugcat = '';

    $html = '<ul class="dropdown-menu" aria-labelledby="dropdownMenu">';

    foreach ($category as $cat) {

        $slugcat .= $slug . '/' . $cat['slug'];

        $html .= '<li class="dropdown-item">
                <a href="' . url($slugcat) . '" data-placement="bottom" type="link"  id="dropdownMenu" ' . (isset($cat['children']) ? 'data-toggle="dropdown"' : '') . '>' . $cat['name'] . (isset($cat['children']) ? ' <i class="fa fa-caret-down"></i>' : '') . '</a>';

        if (isset($cat['children'])) {
            $html .= createSubMenu($cat['children'], $slugcat);
        }

        $html .= '</li>';
        $slugcat = '';
    }
    $html .= '</ul>';
    return $html;
}

function getSystemConfig($optionName, $default = null)
{
    return \App\Logic\SystemConfig::getOption($optionName, $default);
}

function portfolioSize()
{
    $sizes = [
        'tile-lg' => 'tile-lg',
        'tile-xs' => 'tile-xs',
        'tile-sm-land' => 'tile-sm-land',
        'tile-sm' => 'tile-sm',
        'tile-md' => 'tile-md',
        'tile-sm-other' => 'tile-sm-other'
    ];

    return $sizes;
}

function lastday()
{
    $sizes = ["" => "Select Day", 7 => 7, 30 => 30, 90 => 90];

    return $sizes;
}

function imageSize($index, $position)
{
    $sizes = [
        'tile-lg' => [960, 575],
        'tile-xs' => [400, 370],
        'tile-sm-land' => [955, 290],
        'tile-sm' => [640, 640],
        'tile-md' => [640, 570],
        'tile-sm-other' => [640, 290]
    ];

    return $sizes[$index][$position];
}

function portfolioColors()
{
    $colors = [
        'aide' => 'aide',
        'fashion' => 'fashion',
        'pinch' => 'pinch',
        'light-co' => 'light-co',
        'ssl' => 'ssl',
        'trelp' => 'trelp',
        'cars' => 'cars',
        'nausica' => 'nausica',
        'vaingo' => 'vaingo'
    ];

    return $colors;
}

function bladeCompile($value, array $args = array(), $template = null, $developer = null)
{
    $generated = \Blade::compileString($value);

    ob_start() and extract($args, EXTR_SKIP);

    // We'll include the view contents for parsing within a catcher
    // so we can avoid any WSOD errors. If an exception occurs we
    // will throw it out to the exception handler.
    try {
        eval('?>' . $generated);
    }

    // If we caught an exception, we'll silently flush the output
    // buffer so that no partially rendered views get thrown out
    // to the client and confuse the user with junk.
    catch (\Exception $e) {
        ob_get_clean();
        throw $e;
    }

    $content = ob_get_clean();

    return $content;
}

function getShortContent($code)
{
    return optional(\App\Models\Component::active()->where('short_code', $code)->first())->content;
}
function generateOtp()
{
    return rand(1000, 9999);
}

function currency()
{
    return 'NLe';
}

function getBusPermission($param)
{
    $admin = AdminUser::where('id',Auth::guard('admin')->user()->id)->first();
    $module_access = json_decode($admin->access, true);

    return $module_access[$param] ?? false;
}

function getStreetAppPermission($param)
{
    $admin = AdminUser::where('id', Auth::guard('admin')->user()->id)->first();
    $module_access = json_decode($admin->street_module_access, true);

    return $module_access[$param] ?? false;

}

function getPropertyWardPermission($param)
{
    $admin = AdminUser::where('id', Auth::guard('admin')->user()->id)->first();
    $module_access = json_decode($admin->property_module_access, true);

    return $module_access[$param] ?? false;

}

function number_format2Dec($foo)
{
    return number_format((float)$foo, 2, '.', '');
}

function getLicenseRemainingAmount($business_id)
{
    $license_amount_history = LicenseAmountHistory::where('business_id',$business_id)->OrderBy('id','DESC')->first();

    if($license_amount_history)
    {
        if($license_amount_history->due > 0)
        {
            return $license_amount_history->due;
        }
        else if($license_amount_history->due == 0) {
            return 0;
        }
    }
    else
    {
        $businessLic = BusinessLicense::where('BusinessRegId',$business_id)->first();
        return $businessLic->LicenseFee;
    }
}

function getPlenty($business_id)
{
    $license_amount_history = LicenseAmountHistory::where('business_id',$business_id)->OrderBy('id','DESC')->first();
    if($license_amount_history)
    {
        return $license_amount_history->plenty;
    }
    return 0;
}

function countUserAssignedBusiness()
{
    $user_assigned_business = UserAssignedBusiness::where('user_id',Auth::user()->id)->first();
    if(!$user_assigned_business)
    {
        return false;
    }
    return true;
}

function userAssignedBusiness($business_id)
{
    $user = Auth::user()->id;
    $user_assigned_business = UserAssignedBusiness::where('user_id',$user)->where('business_id',$business_id)->first();
    if(!$user_assigned_business)
    {
        return false;
    }
    return true;
}

function countUserAssignedStreetApplication()
{
    $user_assigned_street_application = UserAssignedStreetApplication::where('user_id',Auth::user()->id)->first();
    if(!$user_assigned_street_application)
    {
        return false;
    }
    return true;
}

function userAssignedStreetApplication($street_application_id)
{
    $user = Auth::user()->id;
    $user_assigned_street_application = UserAssignedStreetApplication::where('user_id',$user)->where('street_application_id',$street_application_id)->first();
    if(!$user_assigned_street_application)
    {
        return false;
    }
    return true;
}
