<?php
    namespace App\Helpers;
    use App\Models\Setting;

    class SettingHelper
    {
        static function setting($setting_key)
        {
            $setting = Setting::where('key', $setting_key)->first();
            $value = '';
            if ($setting) {
                $value = $setting->value;
            }
            return $value;
        }
    }
?>