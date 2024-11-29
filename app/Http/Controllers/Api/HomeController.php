<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardRoaster;
use App\Models\PublicHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();
        $todaysDuties = GuardRoaster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', $today)->get();
        $upcomingDuties = GuardRoaster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', '>', $today)->take(2)->get();
        $upcommingHolidays = PublicHoliday::whereDate('date', '>', $today)->take(2)->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'today_duty' =>$todaysDuties,
            'upcoming_duties' => $upcomingDuties,
            'upcoming_holidays' => $upcommingHolidays
        ]);
    }    
}

