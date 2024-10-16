<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt; 

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.setting.index');
    }

    public function paymentSetting()
    {
        return view('admin.setting.payment-setting');
    }

    public function store(Request $request)
    {
        $datas = $request->all();
        $skippedArray = array_slice($datas, 1, null, true);

        $logo = Setting::where('key','logo')->first();
        $oldLogo = $logo ? $logo->value : NULL;
        if ($request->hasFile('logo'))
        {
            $filenameLogo = uploadFile($request->file('logo'), 'uploads/logo/');

            if ($oldLogo && File::exists(public_path($oldLogo))) {
                File::delete(public_path($oldLogo));
            }
        }else {
            $filenameLogo = $oldLogo;
        }
        $skippedArray['logo'] = $filenameLogo;

        if ($request->stripe_api_key) {
            $skippedArray['stripe_api_key'] = Crypt::encryptString($request->stripe_api_key);
        }
        
        if ($request->stripe_secret_key) {
            $skippedArray['stripe_secret_key'] = Crypt::encryptString($request->stripe_secret_key);
        }

        foreach ($skippedArray as $key => $value)
        {
            Setting::updateOrCreate([
                'key' => $key,
            ],[
                'value' => $value
            ]);
        }

        if($request->stripe_api_key && $request->stripe_secret_key) {
            return redirect()->route('settings.payment-setting')->with('success', 'Payment Setting updated successfully');
        } else {
            return redirect()->route('settings.index')->with('success', 'Site Setting updated successfully');
        }

    }
}
