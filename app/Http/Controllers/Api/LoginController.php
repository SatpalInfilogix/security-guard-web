<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' =>$validator->errors()
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success'   => false,
                'message'   => 'User not found'
            ]);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user->token = $user->createToken('MyApp')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User login successfully!',
                'data'    => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ]);
        }
    }
}
