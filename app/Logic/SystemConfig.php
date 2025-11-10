<?php


namespace App\Logic;

use Illuminate\Http\Request;
use App\Models\SystemConfig as SystemConfigModel;

class SystemConfig
{
    /* Option Group Names */
    const COMMUNITY_GROUP = 'community_group';

    /* Option Group Fields */
    const OPTION_GATED_COMMUNITY = 'gated_community';

    /* Option Group Fields */
    const SIGMA_PAY_URL = 'sigma_pay_url';
    //const CURRENCY = 'currency';
    const CURRENCY_RATE = 'currency_rate';

    protected static $configOptionGroup = [
        self::COMMUNITY_GROUP => [
            self::OPTION_GATED_COMMUNITY,
            self::CURRENCY_RATE,
            self::SIGMA_PAY_URL
        ]
    ];

    /* Table fields */
    const OPTION_NAME_FIELD = 'option_name';
    const OPTION_VALUE_FILED = 'option_value';

    public static function getOption($optionName, $default = null)
    {
        static $options;

        if (!$options) {
            $options = SystemConfigModel::pluck(self::OPTION_VALUE_FILED, self::OPTION_NAME_FIELD);
        }


        if (isset($options[$optionName])) {
            return $options[$optionName];
        } else if ($default) {
            return $default;
        } else if ($value = self::getDefaultConfig($optionName)) {
            return $value;
        } else {
            return null;
        }
    }

    public static function getDefaultConfig($optionName)
    {
        return config('config.' . $optionName) ?: null;
    }

    public static function getOptionGroup($optionGroupName)
    {
        $optionSource = SystemConfigModel::whereIn(self::OPTION_NAME_FIELD, self::getOptionGroupFiled($optionGroupName))
            ->pluck(self::OPTION_VALUE_FILED, self::OPTION_NAME_FIELD)->toArray();

        $options = new \stdClass();

        foreach (self::getOptionGroupFiled($optionGroupName) as $optionName) {
            $options->{$optionName} = isset($optionSource[$optionName]) ? $optionSource[$optionName] : self::getDefaultConfig($optionName);
        }

        return $options;
    }


    public static function saveOption($optionName, $optionValue)
    {
        $option = SystemConfigModel::firstOrNew([
            self::OPTION_NAME_FIELD => $optionName
        ]);

        $option->option_value = $optionValue;
        $option->save();

        return $option;
    }

    public static function saveGroupOptions(Request $request, $optionGroupName)
    {
        $optionFields = self::getOptionGroupFiled($optionGroupName);

        \DB::beginTransaction();

        foreach ($optionFields as $field) {
            self::saveOption(
                $field,
                $request->input($field) ?: ''
            );
        }

        \DB::commit();

        return true;
    }

    public static function getOptionGroupFiled($optionGroupName)
    {
        return isset(self::$configOptionGroup[$optionGroupName]) ? self::$configOptionGroup[$optionGroupName] : [];
    }
}
