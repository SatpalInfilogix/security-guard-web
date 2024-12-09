<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Punch;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'         => 'required_without:phone_number|email',
            'phone_number'  => 'required_without:email',
            'password'      => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 400);
        }
    
        $user = $request->filled('email') 
            ? User::where('email', $request->email)->first() 
            : User::where('phone_number', $request->phone_number)->first();
    
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    
        if (Hash::check($request->password, $user->password)) {
            $token = $user->createToken('MyApp')->plainTextToken;
            $user->token = $token;

            $punchStatus = Punch::where('user_id', $user->id)->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
            $user['is_punched_in'] = $punchStatus ? true : false;

            return response()->json([
                'success'   => true,
                'message'   => 'User logged in successfully!',
                'data'      => $user,
                'punchInfo' => $punchStatus,
            ], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password'     => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'  => false,
                'status' => 'VALIDATION_ERROR',
                'message'  => $validator->errors()->first()
            ]);
        }

        $user = Auth::user();

        if(!$user) {
            return response()->json([
                'success' => false,
                'status' => 'USER_NOT_FOUND',
                'message' => 'User not found.'
            ]);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'status' => 'INVALID_OLD_PASSWORD',
                'message' => 'Old password does not match.'
            ]);
        }

        if ($request->old_password === $request->password) {
            return response()->json([
                'success' => false,
                'status' => 'MATCH_NEW_AND_OLD_PASSWORD',
                'message' => 'The new password cannot be the same as the old password.'
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'Status'  => 'SUCCESSFULLY_CREATED',
            'message' => 'Password changed successfully.'
        ]);
    }

}
