<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    
        // Determine if we're searching by email or phone_number
        $user = $request->filled('email') 
            ? User::where('email', $request->email)->first() 
            : User::where('phone_number', $request->phone_number)->first();
    
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    
        // Check if the password is correct
        if (Hash::check($request->password, $user->password)) {
            $token = $user->createToken('MyApp')->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully!',
                'data'    => $user
            ], 200);
        }
    
        return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
    }

}
