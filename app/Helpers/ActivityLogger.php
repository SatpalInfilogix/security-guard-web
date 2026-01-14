<?php

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;

if (!function_exists('logActivity')) {
    function logActivity($activity, $details = null)
    {
        $user = Auth::user();

        UserActivity::create([
            'user_id'    => $user?->id,
            'user_name'  => $user?->first_name . ' ' . $user?->last_name,
            'user_email' => $user?->email,
            'activity'   => $activity,
            'details'    => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}
