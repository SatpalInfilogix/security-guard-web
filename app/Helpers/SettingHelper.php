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

        static function uploadFile($file,  $type)
        {   
            if ($file) {
                $base64_str = substr($file, strpos($file, ",")+1);
                $file = base64_decode($base64_str);
                $filename = uniqid() . '.png';
                $directory = public_path('uploads/activity/' . $type . '/');
                $finalPath = 'uploads/activity/' . $type . '/' . $filename;
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($directory . $filename, $file);
                return $finalPath; 
            }
            return null;
        }

        static function uploadDocument($file)
        {   
            if ($file) {
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $finalPath = 'uploads/user-documents/' . $filename;
                $file->move(public_path('uploads/user-documents/'), $filename);
                return $finalPath; 
            }

            return null;
        }
    }
?>