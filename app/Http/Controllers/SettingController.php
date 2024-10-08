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
            $fileLogo = $request->file('logo');
            $filenameLogo = time().'.'.$fileLogo->getClientOriginalExtension();
            $fileLogo->move(public_path('uploads/logo/'), $filenameLogo);

            $image_path = public_path($oldLogo);

            if ($oldLogo && File::exists($image_path)) {
                File::delete($image_path);
            }
        }
        $skippedArray['logo'] = isset($filenameLogo) ? 'uploads/logo/'.$filenameLogo : $oldLogo;


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
