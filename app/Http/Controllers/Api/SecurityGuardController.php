<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SecurityGuardController extends Controller
{
    public function checkStatus()
    {
        $securityGuardStatus = User::where('id', Auth::id())->first();

        if (!$securityGuardStatus) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guard Status retrieved successfully.',
            'data'    => $securityGuardStatus->status,
        ]);
    }

}
