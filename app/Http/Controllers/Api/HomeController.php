<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardRoster;
use App\Models\PublicHoliday;
use App\Models\Punch;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();
        $todaysDuties = GuardRoster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', $today)->first();
        $upcomingDuties = GuardRoster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', '>', $today)->take(2)->get();
        $upcommingHolidays = PublicHoliday::whereDate('date', '>', $today)->take(2)->get();
        $leaves = Leave::where('guard_id', Auth::id())->whereMonth('date', now()->month)->whereYear('date', now()->year)->get();
        $approvedLeaves = $leaves->where('status', 'Approved')->count();
        $pendingLeaves = $leaves->where('status', 'Pending')->count();
        $rejectedLeaves = $leaves->where('status', 'Rejected')->count();
        $attendances = Punch::where('user_id', Auth::id())->whereMonth('in_time', now()->month)->whereYear('in_time', now()->year)->get();

        $presentDays = 0;
        $halfDays = 0;
        $absentDays = 0;
        $totalDaysInMonth = Carbon::now()->daysInMonth;
        foreach ($attendances as $attendance) {
            $inTime = Carbon::parse($attendance->in_time);
            $outTime = Carbon::parse($attendance->out_time);

            $shiftDuration = $inTime->diffInHours($outTime);
            if ($shiftDuration >= 8) {
                $presentDays++;
            } elseif ($shiftDuration <= 7) {
                $halfDays++;
            }
        }

        $assignedDuties = GuardRoster::where('guard_id', Auth::id())->whereMonth('date', now()->month)->whereYear('date', now()->year)->get();
        foreach ($assignedDuties as $duty) {
            $dutyDate = Carbon::parse($duty->date);
            $attendanceForDuty = $attendances->filter(function ($attendance) use ($dutyDate) {
                return Carbon::parse($attendance->in_time)->isSameDay($dutyDate);
            });
    
            if ($attendanceForDuty->isEmpty()) {
                $absentDays++;
            }
        }

        return response()->json([
            'success'           => true,
            'today_duty'        => $todaysDuties,
            'upcoming_duties'   => $upcomingDuties,
            'upcoming_holidays' => $upcommingHolidays,
            'attendances'       => [
                'presentDays'   => $presentDays,
                'halfDays'      => $halfDays,
                'absentDays'    => $absentDays
            ],
            'leaves'            => [
                'approvedLeaves'=> $approvedLeaves,
                'pendingLeaves' => $pendingLeaves,
                'rejectedLeaves'=> $rejectedLeaves
            ]
        ]);
    }    
}

