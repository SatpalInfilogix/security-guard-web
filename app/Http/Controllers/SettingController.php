<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.setting.index');
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

        foreach ($skippedArray as $key => $value)
        {
            Setting::updateOrCreate([
                'key' => $key,
            ],[
                'value' => $value
            ]);
        }

        return redirect()->route('settings.index')->with('success', 'Setting updated successfully');
    }
}
