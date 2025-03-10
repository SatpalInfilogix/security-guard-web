<?php

namespace App\Http\Controllers;

use App\Models\FortnightDates;
use App\Models\TwentyTwoDayInterval;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FortnightDatesController extends Controller
{
    public function index()
    {
        $fortnightsDates = FortnightDates::get();
        $today = Carbon::now();
        $currentFortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        return view('admin.fortnight-dates.index', compact('fortnightsDates', 'currentFortnightDays'));
    }

    public function listingTwentyTwoDays()
    {
        $twentyTwoDaysIntervals = TwentyTwoDayInterval::get();
        $today = Carbon::now();
        $currentInterval = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_Date', '>=', $today)->first();

        return view('admin.twenty-two-days.index', compact('twentyTwoDaysIntervals', 'currentInterval'));
    }
}
