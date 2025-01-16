<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Punch;
use App\Models\PublicHoliday;
use App\Models\RateMaster;
use App\Models\FortnightDates;
use App\Models\GuardAdditionalInformation;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function getAttendance(Request $request)
    {
        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();                         
        if (!$fortnight) {
            $fortnight = null;
        }
        // $month = Carbon::now()->format('m-Y');
        // if ($request->month) {
        //     $month = $request->month;
        // }

        // $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::createFromFormat('m-Y', $month)->startOfMonth();
        // $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::createFromFormat('m-Y', $month)->endOfMonth();

        $previousFortnight = FortnightDates::whereDate('end_date', '<', $today)->orderByDesc('end_date')->first();
        
        if (!$previousFortnight) {
            $previousFortnight = $fortnight;
        }

        $startDateCurrent = Carbon::parse($fortnight->start_date);
        $endDateCurrent = Carbon::parse($fortnight->end_date);

        $startDatePrevious = Carbon::parse($previousFortnight->start_date);
        $endDatePrevious = Carbon::parse($previousFortnight->end_date);

        if ($request->start_date && $request->end_date) {
            $startCustomDate = Carbon::parse($request->start_date);
            $endCustomDate = Carbon::parse($request->end_date);
        } else {
            $startCustomDate = null;
            $endCustomDate = null;
        }

        if (($startCustomDate && $endCustomDate) && ($startCustomDate->greaterThan($endCustomDate))) {
            return response()->json(['error' => 'Start date must be before end date'], 400);
        }
    
        // if ($startDateCurrent->greaterThan($endDateCurrent) || $startDatePrevious->greaterThan($endDatePrevious) || ($)) {
        //     return response()->json(['error' => 'Start date must be before end date'], 400);
        // }

        $publicHolidaysCurrent = $this->getPublicHolidays($startDateCurrent, $endDateCurrent);
        $publicHolidaysPrevious = $this->getPublicHolidays($startDatePrevious, $endDatePrevious);
        
        if ($startCustomDate && $endCustomDate) {
            $publicHolidaysCustom = $this->getPublicHolidays($startCustomDate, $endCustomDate);
            $attedanceCustomData = $this->getAttendanceData($startCustomDate, $endCustomDate, $publicHolidaysCustom);
        } else {
            $attedanceCustomData = [];
        }

        $attendanceDataCurrent = $this->getAttendanceData($startDateCurrent, $endDateCurrent, $publicHolidaysCurrent);
        $attendanceDataPrevious = $this->getAttendanceData($startDatePrevious, $endDatePrevious, $publicHolidaysPrevious);
        
        return response()->json([
            'status'  => true,
            'message' => 'Attendance',
            'currentFortnight' => $attendanceDataCurrent,
            'previousFortnight' => $attendanceDataPrevious,
            'customData'    => $attedanceCustomData,
        ]);
    }

    private function getAttendanceData($startDate, $endDate, $publicHolidays)
    {
        $attendanceData = [];
        $today = Carbon::now();

        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $punchRecord = Punch::where('user_id', Auth::id())->whereDate('in_time', $date)->whereNotNull('in_time')->whereNotNull('out_time')->first();

            $workedHours = 0;
            $loggedHours = 0;
            $regularHours = 0;
            $regularHoursEarning = 0;
            $publicHolidayEarning = 0;
            $overtimeEarning = 0;
            $overtimeHours = 0;
            $status = 'absent';

            if ($punchRecord) {
                $inTime = Carbon::parse($punchRecord->in_time);
                $outTime = Carbon::parse($punchRecord->out_time);

                if ($inTime < $outTime) {
                    $workedHours = $inTime->diffInHours($outTime);
                    $loggedHours = $workedHours;
                    $regularHours = 8;

                    if ($workedHours > $regularHours) {
                        $overtimeHours = $workedHours - $regularHours;
                        $workedHours = $regularHours; // Cap to normal hours
                    }
                }

                $guardAdditionalInformation = GuardAdditionalInformation::where('user_id', Auth::id())->first();
                $rateMater = RateMaster::where('id', $guardAdditionalInformation->guard_type_id)->first();
                if ($rateMater) {
                    if ($publicHolidays->contains($date->toDateString())) {
                        $publicHolidayEarning = $workedHours * $rateMater->holiday_rate; // Use public holiday rate
                    } else {
                        $regularHoursEarning = $workedHours * $rateMater->gross_hourly_rate;
                        $overtimeEarning = $overtimeHours * $rateMater->overtime_rate;
                    }
                }
                $status = 'present';
            } else {
                if ($date->isToday()) {
                    $status = 'pending';
                } elseif ($date->isAfter($today)) {
                    $status = 'pending';
                } else {
                    $status = 'absent';
                }
            }

            $attendanceData[] = [
                'date'                   => $date->toDateString(),
                'status'                 => $status,
                'LoggedHours'            => round($loggedHours, 2),
                'holiday'                => $publicHolidays->contains($date->toDateString()) ? 'Public Holiday' : '',
                'regularHours'           => $regularHours,
                'regularHoursEarning'    => $regularHoursEarning,
                'overtimeHours'          => $overtimeHours,
                'overtimeEarning'        => $overtimeEarning,
                'publicHolidayEarning'  => $publicHolidayEarning,
            ];
        }

        return $attendanceData;
    }
    
    private function getPublicHolidays($startDate, $endDate)
    {
        $holidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])->pluck('date');

        return collect($holidays);
    }

    private function getHourlyRate($userId)
    {
        return 20;
    }
}
