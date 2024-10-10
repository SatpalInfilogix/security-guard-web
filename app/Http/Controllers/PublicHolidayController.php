<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index()
    {
        $publicHolidays = PublicHolday::latest()->get();

        return view('admin.public-hodays.index', compact('publicHolidays'));
    }

    
}
