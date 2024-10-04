<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PunchTable;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = PunchTable::with('user')->latest()->get();

        return view('admin.attendance.index', compact('attendances'));
    }
}
