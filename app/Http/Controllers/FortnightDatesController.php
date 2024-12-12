<?php

namespace App\Http\Controllers;

use App\Models\FortnightDates;
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
}
