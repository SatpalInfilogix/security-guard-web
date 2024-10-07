<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        ]);

        $user = User::where('id', Auth::id())->first();
        $oldProfile = NULL;
        if($user != '') {
            $oldProfile = $user->profile_picture;
        }

        if ($request->hasFile('profile_image'))
        {
            $file = $request->file('profile_image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/profile-pic/'), $filename);
        }

        $user->update([
            'first_name'      => $request->first_name,
            'last_name'       => $request->last_name,
            'phone_number'    => $request->phone_no,
            'profile_picture' => isset($filename) ? 'uploads/profile-pic/'. $filename : $oldProfile,
        ]);

        return redirect()->route('profile.index')->with('success', 'Profile updated successfully');
    }
}
