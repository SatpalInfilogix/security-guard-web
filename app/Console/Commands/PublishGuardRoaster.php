<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FortnightDates;
use App\Models\GuardRoster;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use App\Models\Punch;
use App\Models\User;
use Spatie\Permission\Models\Role;

class PublishGuardRoaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:guard-roaster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guard Roaster';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if ($fortnightDays) {
            $endDate = Carbon::parse($fortnightDays->end_date)->startOfDay();
            $differenceInDays = $today->diffInDays($endDate, false); 
            $nextStartDate = Carbon::parse($fortnightDays->end_date)->addDay();
            $nextEndDate = $nextStartDate->copy()->addDays(13);

            if(carbon::parse($fortnightDays->start_date) == $today) {
                $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
                $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);

                $publicHolidays = PublicHoliday::whereBetween('date', [$previousFortnightStartDate, $previousFortnightEndDate])->pluck('date')->toArray();
                $attendances = Punch::with('user')->whereBetween('in_time', [$previousFortnightStartDate, $previousFortnightEndDate])->latest()
                                    ->select('id', 'user_id', 'in_time', 'in_lat', 'in_long', 'in_image', 'out_time', 'out_lat', 'out_long', 'out_image', 'created_at', 'updated_at')
                                    ->get();

                $groupedAttendances = $attendances->groupBy('user_id');

                $userHours = [];
                foreach ($groupedAttendances as $userId => $attendancesForUser)
                {
                    $attendanceDetails = $attendancesForUser->groupBy(function ($attendance) {
                        return Carbon::parse($attendance->in_time)->toDateString();
                    })->toArray();

                    $userHours[$userId] = $this->calculateUserHours($attendanceDetails, $publicHolidays);

                    $existingPayroll = Payroll::where('guard_id', $userId)->where('start_date', $previousFortnightStartDate->format('Y-m-d'))
                                                ->where('end_date', $previousFortnightEndDate->format('Y-m-d'))->first();

                if (!$existingPayroll) {
                    $payrollData = Payroll::create([
                        'guard_id' => $userId,
                        'start_date' => $previousFortnightStartDate->format('Y-m-d'),
                        'end_date' => $previousFortnightEndDate->format('Y-m-d'),
                        'normal_hours' => $userHours[$userId]['total_normal_hours'],
                        'overtime' => $userHours[$userId]['total_overtime_hours'],
                        'public_holidays' => $userHours[$userId]['total_public_holiday_hours'],
                    ]);
                } else {
                    $payrollData = $existingPayroll;
                }
                    $this->createPayrollDetails($payrollData->id, $userId, $attendanceDetails, $publicHolidays);
                }
            } else if ($differenceInDays == 2) {
                $roster = GuardRoster::where('date', '>=', $fortnightDays->start_date)->where('end_date', '<=', $fortnightDays->end_date)->get();

                $nextFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
                if ($nextFortnightRoster->isEmpty()) {
                    foreach ($roster as $currentRoster) {
                        $shiftedDate = Carbon::parse($currentRoster->date)->addDays(14);  // Shifted date by 14 days
                        $startTime = Carbon::parse($currentRoster->start_time);
                        $endTime = Carbon::parse($currentRoster->end_time);

                        $existingRoster = GuardRoster::where('guard_id', $currentRoster->guard_id)->where('client_site_id', $currentRoster->client_site_id)->where('date', '=', $shiftedDate->format('Y-m-d'))->first();

                        if ($existingRoster) {
                            continue;
                        }

                        $endDate = $shiftedDate->copy();
                        if ($endTime->lessThan($startTime)) {
                            $endDateForNextRoster = $endDate->addDay();
                        } else {
                            $endDateForNextRoster = $endDate;
                        }

                        GuardRoster::create([
                            'guard_id' => $currentRoster->guard_id,
                            'client_id' => $currentRoster->client_id,
                            'client_site_id' => $currentRoster->client_site_id,
                            'date' => $shiftedDate->format('Y-m-d'),
                            'end_date' => $endDateForNextRoster->format('Y-m-d'),
                            'start_time' => $currentRoster->start_time,
                            'end_time' => $currentRoster->end_time,
                        ]);
                    }
                }
            } else if ($differenceInDays == 1) {
                $newFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
                foreach ($newFortnightRoster as $currentRoster) {
                    $currentRoster->update([
                        'is_publish' => 1
                    ]);
                }
            }
        }
    }

    protected function calculateUserHours($attendanceDetails, $publicHolidays)
    {
        $totalNormalHours = 0;
        $totalNormalMinutes = 0;
        $totalOvertimeHours = 0;
        $totalOvertimeMinutes = 0;
        $totalPublicHolidayHours = 0;
        $totalPublicHolidayMinutes = 0;
        $regularHours = 8;

        foreach ($attendanceDetails as $attendanceDate => $attendancesForDay) {
            $totalWorkedMinutesForDay = 0;

            foreach ($attendancesForDay as $attendance) {
                $inTime = Carbon::parse($attendance['in_time']);
                $outTime = Carbon::parse($attendance['out_time']);

                $workedMinutes = $inTime->diffInMinutes($outTime);
                $totalWorkedMinutesForDay += $workedMinutes;
            }

            $isPublicHoliday = in_array($attendanceDate, $publicHolidays);

            if ($isPublicHoliday) {
                $totalPublicHolidayMinutes += $totalWorkedMinutesForDay;
            } else {
                if ($totalWorkedMinutesForDay <= $regularHours * 60) {
                    $totalNormalMinutes += $totalWorkedMinutesForDay;
                } else {
                    $totalNormalMinutes += $regularHours * 60; // 8 hours in minutes
                    $totalOvertimeMinutes += ($totalWorkedMinutesForDay - ($regularHours * 60)); // Remaining goes to overtime
                }
            }
        }

        $extraNormalHours = intdiv($totalNormalMinutes, 60);
        $totalNormalHours = $extraNormalHours;
        $totalNormalMinutes = $totalNormalMinutes % 60;

        $extraOvertimeHours = intdiv($totalOvertimeMinutes, 60);
        $totalOvertimeHours = $extraOvertimeHours;
        $totalOvertimeMinutes = $totalOvertimeMinutes % 60;

        $extraPublicHolidayHours = intdiv($totalPublicHolidayMinutes, 60);
        $totalPublicHolidayHours = $extraPublicHolidayHours;
        $totalPublicHolidayMinutes = $totalPublicHolidayMinutes % 60;

        return [
            'total_normal_hours' => $totalNormalHours . '.' . str_pad($totalNormalMinutes, 2, '0', STR_PAD_LEFT),
            'total_overtime_hours' => $totalOvertimeHours . '.' . str_pad($totalOvertimeMinutes, 2, '0', STR_PAD_LEFT),
            'total_public_holiday_hours' => $totalPublicHolidayHours . '.' . str_pad($totalPublicHolidayMinutes, 2, '0', STR_PAD_LEFT),
        ];
    }

    protected function createPayrollDetails($payrollId, $userId, $attendanceDetails, $publicHolidays)
    {
        $regularWorkingHoursPerDay = 8;

        foreach ($attendanceDetails as $attendanceDate => $attendanceDetail) {
            $existingPayrollDetail = PayrollDetail::where('payroll_id', $payrollId)->where('guard_id', $userId)
                                                    ->where('date', $attendanceDate)->first();

            if (!$existingPayrollDetail) {
                $totalWorkedMinutes = 0;
                $regularMinutes = 0;
                $overtimeMinutes = 0;
                $publicHolidayMinutes = 0;

                foreach ($attendanceDetail as $attendanceForDay) {
                    $inTime = Carbon::parse($attendanceForDay['in_time']);
                    $outTime = Carbon::parse($attendanceForDay['out_time']);

                    $workedMinutes = $inTime->diffInMinutes($outTime);
                    $totalWorkedMinutes += $workedMinutes;
                }

                $isPublicHoliday = in_array($attendanceDate, $publicHolidays);

                if ($isPublicHoliday) {
                    $publicHolidayMinutes = $totalWorkedMinutes;
                } else {
                    if ($totalWorkedMinutes <= $regularWorkingHoursPerDay * 60) {
                        $regularMinutes = $totalWorkedMinutes;
                    } else {
                        $regularMinutes = $regularWorkingHoursPerDay * 60;
                        $overtimeMinutes = $totalWorkedMinutes - ($regularWorkingHoursPerDay * 60);
                    }
                }

                $regularHours = intdiv($regularMinutes, 60);
                $regularRemainingMinutes = $regularMinutes % 60;

                $overtimeHours = intdiv($overtimeMinutes, 60);
                $overtimeRemainingMinutes = $overtimeMinutes % 60;

                $publicHolidayHours = intdiv($publicHolidayMinutes, 60);
                $publicHolidayRemainingMinutes = $publicHolidayMinutes % 60;

                $user = User::with('guardAdditionalInformation')->where('id', $userId)->first();

                PayrollDetail::create([
                    'payroll_id' => $payrollId,
                    'guard_id' => $userId,
                    'guard_type_id' => $user->guardAdditionalInformation->guard_type_id,
                    'date' => $attendanceDate,
                    'normal_hours' => $regularHours . '.' . str_pad($regularRemainingMinutes, 2, '0', STR_PAD_LEFT),
                    'overtime' => $overtimeHours . '.' . str_pad($overtimeRemainingMinutes, 2, '0', STR_PAD_LEFT),
                    'public_holiday' => $publicHolidayHours . '.' . str_pad($publicHolidayRemainingMinutes, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
