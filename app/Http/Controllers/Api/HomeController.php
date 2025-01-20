<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FortnightDates;
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
        $today = Carbon::now();
        $fortnightDate = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        $startDate = Carbon::parse($fortnightDate->start_date);
        $endDate = Carbon::parse($fortnightDate->end_date);
        $todaysDuties = GuardRoster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', $today)->first();
        $upcomingDuties = GuardRoster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', '>', $startDate)->whereDate('date', '<=', $endDate)->take(2)->get();
        $upcommingHolidays = PublicHoliday::whereDate('date', '>', $today)->take(2)->get();
        $leaves = Leave::where('guard_id', Auth::id())->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)->get();
        $approvedLeaves = $leaves->where('status', 'Approved')->count();
        $pendingLeaves = $leaves->where('status', 'Pending')->count();
        $rejectedLeaves = $leaves->where('status', 'Rejected')->count();
        $attendances = Punch::where('user_id', Auth::id())->whereDate('in_time', '>=', $startDate)->whereDate('in_time', '<=', $endDate)->get();

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

        $assignedDuties = GuardRoster::where('guard_id', Auth::id())->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)->get();        
        foreach ($assignedDuties as $duty) {
            $dutyDate = Carbon::parse($duty->date);
            if ($dutyDate->isToday() || $dutyDate->isBefore(Carbon::today())) {
                $attendanceForDuty = $attendances->filter(function ($attendance) use ($dutyDate) {
                    return Carbon::parse($attendance->in_time)->isSameDay($dutyDate);
                });

                if ($attendanceForDuty->isEmpty()) {
                    $absentDays++;
                }
            }
        }

        $previousFortnightDate = FortnightDates::whereDate('end_date', '<', $startDate)->orderBy('end_date', 'desc')->first();
        if ($previousFortnightDate) {
            $previousStartDate = Carbon::parse($previousFortnightDate->start_date);
            $previousEndDate = Carbon::parse($previousFortnightDate->end_date);

            $previousAttendances = Punch::where('user_id', Auth::id())->whereDate('in_time', '>=', $previousStartDate)
                ->whereDate('in_time', '<=', $previousEndDate)->get();

            $previousPresentDays = 0;
            $previousHalfDays = 0;
            $previousAbsentDays = 0;

            foreach ($previousAttendances as $attendance) {
                $inTime = Carbon::parse($attendance->in_time);
                $outTime = Carbon::parse($attendance->out_time);

                $shiftDuration = $inTime->diffInHours($outTime);
                if ($shiftDuration >= 8) {
                    $previousPresentDays++;
                } elseif ($shiftDuration <= 7) {
                    $previousHalfDays++;
                }
            }

            $previousAssignedDuties = GuardRoster::where('guard_id', Auth::id())->whereDate('date', '>=', $previousStartDate)->whereDate('date', '<=', $previousEndDate)->get();

            foreach ($previousAssignedDuties as $duty) {
                $dutyDate = Carbon::parse($duty->date);
                $attendanceForDuty = $previousAttendances->filter(function ($attendance) use ($dutyDate) {
                    return Carbon::parse($attendance->in_time)->isSameDay($dutyDate);
                });

                if ($attendanceForDuty->isEmpty()) {
                    $previousAbsentDays++;
                }
            }

            $previousFortnightStats = [
                'presentDays' => $previousPresentDays,
                'halfDays' => $previousHalfDays,
                'absentDays' => $previousAbsentDays,
            ];
        } else {
            $previousFortnightStats = [
                'presentDays' => 0,
                'halfDays' => 0,
                'absentDays' => 0,
            ];
        }

        return response()->json([
            'success'           => true,
            'today_duty'        => $todaysDuties,
            'upcoming_duties'   => $upcomingDuties,
            'upcoming_holidays' => $upcommingHolidays,
            'current_attendances' => [
                'presentDays'   => $presentDays,
                'halfDays'      => $halfDays,
                'absentDays'    => $absentDays
            ],
            'previous_attendances' => $previousFortnightStats,
            'leaves'            => [
                'approvedLeaves'=> $approvedLeaves,
                'pendingLeaves' => $pendingLeaves,
                'rejectedLeaves'=> $rejectedLeaves
            ]
        ]);
    }    
}

