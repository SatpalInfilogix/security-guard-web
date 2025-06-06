<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    public function index()
    {
        $user = User::where('id', Auth::id())->first();

        return view('admin.profile.index', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name'    => 'required',
            'last_name'     => 'required',
            'phone_no'      => 'required',
            'profile_image'   => 'nullable',
            'password'        => 'nullable|min:4',
        ]);

        $user = User::where('id', Auth::id())->first();
        $oldProfile = $user ? $user->profile_picture : NULL;
        if ($request->hasFile('profile_image')) {
            $filename = uploadFile($request->file('profile_image'), 'uploads/profile-pic/');

            if ($oldProfile && File::exists(public_path($oldProfile))) {
                File::delete(public_path($oldProfile));
            }
        } else {
            $filename = $oldProfile;
        }

        $user->update([
            'first_name'      => $request->first_name,
            'last_name'       => $request->last_name,
            'phone_number'    => $request->phone_no,
            'profile_picture' => $filename,
            'password'        =>$request->password,    
        ]);

        return redirect()->route('profile.index')->with('success', 'Profile updated successfully');
    }
}
