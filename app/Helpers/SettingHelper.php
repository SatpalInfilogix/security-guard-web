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

        static function uploadFile($file,  $path)
        {   
            if ($file) {
                $imageName = uniqid() . '.' . $file->getClientOriginalExtension();
                $imagePath = $path . $imageName;
                $file->move(public_path($path), $imageName);

                return $imagePath;
            }

            return null;
        }
    }
?>