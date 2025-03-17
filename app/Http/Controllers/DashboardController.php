<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\ClientSite;
use App\Models\Client;
use App\Models\FortnightDates;
use App\Models\GuardRoster;
use App\Models\Punch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $guardRole = Role::where('id', 3)->first();
        $securityGuards = User::whereHas('roles', function ($query) use ($guardRole) {
            $query->where('role_id', $guardRole->id);
        })->count();

        $roleEmployee = Role::where('id', 9)->first();
        $employees = User::whereHas('roles', function ($query) use ($roleEmployee) {
            $query->where('role_id', $roleEmployee->id);
        })->count();

        $clients = Client::count();
        $clientSites = ClientSite::count();

        $dateRange = $request->input('date');
        $dates = explode(' to ', $dateRange);

        if (count($dates) === 2) {
            $startDate = Carbon::parse($dates[0]);
            $endDate = Carbon::parse($dates[1]);
        } else {
            $today = Carbon::now();
            $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
            $startDate = Carbon::parse($fortnightDays->start_date);
            $endDate = Carbon::parse($fortnightDays->end_date);
        }

        $guardDuties = GuardRoster::whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)->orderBy('date', 'asc')->orderBy('start_time', 'asc')->get();

        $listingData = [];
        foreach ($guardDuties as $duty) {
            $startTime = Carbon::parse($duty->date . ' ' . $duty->start_time);
            $endTime = Carbon::parse($duty->date . ' ' . $duty->end_time);

            $attendances = Punch::with('user')->whereDate('in_time', $duty->date)->where('client_site_id', $duty->client_site_id)
                                ->whereNotNull('in_time')->whereNotNull('out_time')->orderBy('in_time', 'asc')->get();

            $groupedAttendances = $attendances->groupBy('user_id');

            foreach ($groupedAttendances as $userId => $userAttendances) {
                $user = $userAttendances->first()->user;
                $totalWorkingMinutes = 0;
                $intervals = [];
                $lateArrival = false;
                $earlyDeparture = false;

                $firstPunchInTime = null;
                $lastPunchOutTime = null;

                $isDoubleEntry = $userAttendances->count() > 1;

                foreach ($userAttendances as $attendance) {
                    $inTime = Carbon::parse($attendance->in_time);
                    $outTime = Carbon::parse($attendance->out_time);

                    if ($inTime->between($startTime, $endTime) || $outTime->between($startTime, $endTime)) {
                        if (!$firstPunchInTime) {
                            $firstPunchInTime = $inTime;
                        }
                        $lastPunchOutTime = $outTime;

                        $workingMinutes = $inTime->diffInMinutes($outTime);
                        $totalWorkingMinutes += $workingMinutes;

                        $intervals[] = [
                            'in_time' => $inTime->format('h:i A'),
                            'out_time' => $outTime->format('h:i A'),
                            'working_hours' => floor($workingMinutes / 60) . 'h ' . ($workingMinutes % 60) . 'm',
                        ];
                    }
                }

                if ($firstPunchInTime && $firstPunchInTime->gt($startTime)) {
                    $lateArrival = true;
                }
                if ($lastPunchOutTime && $lastPunchOutTime->lt($endTime)) {
                    $earlyDeparture = true;
                }

                if ($isDoubleEntry || $lateArrival || $earlyDeparture) {
                    $totalWorkingHours = floor($totalWorkingMinutes / 60) . 'h ' . ($totalWorkingMinutes % 60) . 'm';
                    $listingData[] = [
                        'user' => $user->first_name,
                        'date' => $duty->date,
                        'expected_in' => $startTime->format('h:i A'),
                        'expected_out' => $endTime->format('h:i A'),
                        'intervals' => $intervals,
                        'total_working_hours' => $totalWorkingHours,
                        'punched_in_late' => $lateArrival ? "Arrived Late" : "N/A",
                        'punched_out_early' => $earlyDeparture ? "Left Early" : "N/A",
                    ];
                }
            }
        }

        return view('admin.dashboard.index', compact('securityGuards','clients','employees','clientSites','listingData','startDate','endDate'));
    }
}
