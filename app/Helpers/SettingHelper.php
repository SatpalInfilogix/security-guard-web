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

        static function uploadFile($file)
        {   
            if ($file) {
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $finalPath = 'uploads/guard-image/' . $filename;
                $file->move(public_path('uploads/guard-image/'), $filename);
                return $finalPath; 
            }

            return null;
        }
    }
?>