<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Punch;
use App\Models\PublicHoliday;
use App\Models\RateMaster;
use App\Models\GuardAdditionalInformation;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function getAttendance(Request $request)
    {
        $month = Carbon::now()->format('m-Y');
        if ($request->month) {
            $month = $request->month;
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::createFromFormat('m-Y', $month)->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::createFromFormat('m-Y', $month)->endOfMonth();

        if ($startDate->greaterThan($endDate)) {
            return response()->json(['error' => 'Start date must be before end date'], 400);
        }

        $publicHolidays = $this->getPublicHolidays($startDate, $endDate);

        $attendanceData = [];
        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $punchRecord = Punch::where('user_id', Auth::id())->whereDate('in_time', $date)->whereNotNull('in_time')->whereNotNull('out_time')->first();

            $workedHours = 0;
            $loggedHours = 0;
            $regularHours = 0;
            $regularHoursEarning = 0;
            $publicHolidayEarning = 0;
            $overtimeEarning = 0;
            $overtimeHours = 0;

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
                if($rateMater) {
                    if ($publicHolidays->contains($date->toDateString())) {
                        $publicHolidayEarning = $workedHours * $rateMater->holiday_rate; // Use public holiday rate
                    } else {
                        $regularHoursEarning = $workedHours * $rateMater->gross_hourly_rate;
                        $overtimeEarning = $overtimeHours * $rateMater->overtime_rate;
                    }
                }
            }
    
            $attendanceData[] = [
                'date'             => $date->toDateString(),
                'status'           => $punchRecord ? 'present' : 'absent',
                'LoggedHours'      => $loggedHours,
                'holiday'          => $publicHolidays->contains($date->toDateString()) ? 'Public Holiday' : '',
                'regularHours'     => $regularHours,
                'regularHoursEarning' => $regularHoursEarning,
                'overtimeHours'    => $overtimeHours,
                'overtimeEarning'  => $overtimeEarning,
                'publicHolidayEarning' => $publicHolidayEarning,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Attendance',
            'data'    => $attendanceData,
        ]);
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
