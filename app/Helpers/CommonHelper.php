<?php

use App\Models\Setting;

if(!function_exists('uploadFile')) {
    function uploadFile($file,  $path) {   
        if ($file) {
            $imageName = uniqid() . '.' . $file->getClientOriginalExtension();
            $imagePath = $path . $imageName;
            $file->move(public_path($path), $imageName);
            return $imagePath;
        }
        return null;
    }
}

if(!function_exists('setting')) {
    function setting($setting_key){
        $setting = Setting::where('key', $setting_key)->first();
        $value = '';
        if ($setting) {
            $value = $setting->value;
        }
        return $value;
    }
}
