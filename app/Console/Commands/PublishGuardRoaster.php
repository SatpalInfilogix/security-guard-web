<?php

namespace App\Console\Commands;

use App\Models\ClientSite;
use App\Models\Deduction;
use App\Models\DeductionDetail;
use Illuminate\Console\Command;
use App\Models\FortnightDates;
use App\Models\GuardLeaveEncashment;
use App\Models\GuardRoster;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use App\Models\Punch;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\RateMaster;
use App\Models\Leave;
use App\Models\GuardTaxThreshold;
use Google\Service\Sheets\NumberFormat;
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
        //To manually execute payroll crons we need to put manual date which will be +6 days from the current fortnights end date.
        $today = Carbon::now()->startOfDay();
        // $today = Carbon::parse("03-02-2025")->startOfDay(); //--Manual Check

        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if ($fortnightDays) {
            $endDate = Carbon::parse($fortnightDays->end_date)->startOfDay();
            $differenceInDays = $today->diffInDays($endDate, false);
            $nextStartDate = Carbon::parse($fortnightDays->end_date)->addDay();
            $nextEndDate = $nextStartDate->copy()->addDays(13);

            $sixthDay = Carbon::parse($fortnightDays->start_date)->addDays(6);
            // $sixthDay = Carbon::parse("03-02-2025"); //--Manual Check
            $isPublishDate =  Carbon::parse($sixthDay)->addDays(3);

            $eightDay = Carbon::parse($fortnightDays->start_date)->addDays(7);
            // $eightDay = Carbon::parse("04-02-2025"); //--Manual Check

            $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
            $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
            if ($eightDay == $today) {
                $firstWeekStart = $previousFortnightStartDate;
                $firstWeekEnd = $firstWeekStart->copy()->addDays(6);

                $secondWeekStart = $firstWeekEnd->copy()->addDay();
                $secondWeekEnd = $previousFortnightEndDate;

                $firstWeekPayroll = PayrollDetail::whereBetween('date', [$firstWeekStart->format('Y-m-d'), $firstWeekEnd->format('Y-m-d')])->get();
                $secondWeekPayroll = PayrollDetail::whereBetween('date', [$secondWeekStart->format('Y-m-d'), $secondWeekEnd->format('Y-m-d')])->get();

                $firstWeekInvoice = $this->generateInvoice($firstWeekPayroll, $firstWeekStart, $firstWeekEnd);
                $secondWeekInvoice = $this->generateInvoice($secondWeekPayroll, $secondWeekStart, $secondWeekEnd);
            }

            if ($sixthDay == $today) {
                $firstWeekStart = $previousFortnightStartDate;
                $firstWeekEnd = $firstWeekStart->copy()->addDays(6);

                $secondWeekStart = $firstWeekEnd->copy()->addDay();
                $secondWeekEnd = $previousFortnightEndDate;

                $publicHolidays = PublicHoliday::whereBetween('date', [$previousFortnightStartDate, $previousFortnightEndDate])->pluck('date')->toArray();
                $previousStartDate = $previousFortnightStartDate->format('Y-m-d');
                $previousEndDate = $previousFortnightEndDate->format('Y-m-d');
                $attendances = Punch::with('user')->whereDate('in_time', '>=', $previousStartDate)->whereDate('in_time', '<=', $previousEndDate)
                    ->select('id', 'user_id', 'guard_type_id', 'client_site_id', 'in_time', 'out_time', 'regular_rate', 'laundry_allowance', 'canine_premium', 'fire_arm_premium', 'gross_hourly_rate', 'overtime_rate', 'holiday_rate', 'late_min')->get();

                $groupedAttendances = $attendances->groupBy('user_id');

                foreach ($groupedAttendances as $userId => $attendancesForUser) {
                    $attendanceDetails = $attendancesForUser->groupBy(function ($attendance) {
                        return Carbon::parse($attendance->in_time)->toDateString();
                    })->toArray();

                    $existingPayroll = Payroll::where('guard_id', $userId)->where('start_date', $previousFortnightStartDate->format('Y-m-d'))
                        ->where('end_date', $previousFortnightEndDate->format('Y-m-d'))->first();

                    if (!$existingPayroll) {
                        $payrollData = Payroll::create([
                            'guard_id'              => $userId,
                            'start_date'            => $previousFortnightStartDate->format('Y-m-d'),
                            'end_date'              => $previousFortnightEndDate->format('Y-m-d'),
                        ]);
                    } else {
                        $payrollData = $existingPayroll;
                    }
                    $this->createPayrollDetails($payrollData->id, $userId, $attendanceDetails, $publicHolidays, $firstWeekStart, $firstWeekEnd, $secondWeekStart, $secondWeekEnd);
                    $this->calculatePayrollUserHours($payrollData->id, $userId, $attendanceDetails, $publicHolidays, $previousStartDate, $previousEndDate);
                }
            }

            if ($isPublishDate == $today) {
                $payrollPublished = Payroll::where('start_date', $previousFortnightStartDate->format('Y-m-d'))
                    ->where('end_date', $previousFortnightEndDate->format('Y-m-d'))->get();

                foreach ($payrollPublished as $payroll) {
                    $payroll->update([
                        'is_publish' => 1
                    ]);
                }
            }
            if ($differenceInDays == 2) {
                $roster = GuardRoster::where('date', '>=', $fortnightDays->start_date)->where('end_date', '<=', $fortnightDays->end_date)->get();

                $nextFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
                if ($nextFortnightRoster->isEmpty()) {
                    foreach ($roster as $currentRoster) {
                        $shiftedDate = Carbon::parse($currentRoster->date)->addDays(14);
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
                            'guard_type_id'  => $currentRoster->guard_type_id,
                            'date' => $shiftedDate->format('Y-m-d'),
                            'end_date' => $endDateForNextRoster->format('Y-m-d'),
                            'start_time' => $currentRoster->start_time,
                            'end_time' => $currentRoster->end_time,
                        ]);
                    }
                }
            }
            if ($differenceInDays == 1) {
                $newFortnightRoster = GuardRoster::whereDate('date', '>=', $nextStartDate)->whereDate('end_date', '<=', $nextEndDate)->get();
                foreach ($newFortnightRoster as $currentRoster) {
                    $currentRoster->update([
                        'is_publish' => 1
                    ]);
                }
            }
        }
    }

    protected function createPayrollDetails($payrollId, $userId, $attendanceDetails, $publicHolidays, $firstWeekStart, $firstWeekEnd, $secondWeekStart, $secondWeekEnd)
    {
        $regularWorkingHoursPerDay = 8 * 60;
        $firstWeekAttendanceDetails = [];
        $secondWeekAttendanceDetails = [];

        foreach ($attendanceDetails as $attendanceDate => $attendanceDetail) {
            $attendanceDateCarbon = Carbon::parse($attendanceDate);
            if ($attendanceDateCarbon->between($firstWeekStart, $firstWeekEnd)) {
                $firstWeekAttendanceDetails[$attendanceDate] = $attendanceDetail;
            } elseif ($attendanceDateCarbon->between($secondWeekStart, $secondWeekEnd)) {
                $secondWeekAttendanceDetails[$attendanceDate] = $attendanceDetail;
            }
        }
        $this->processWeekPayrollDetails($payrollId, $userId, $firstWeekAttendanceDetails, $publicHolidays, $firstWeekStart, $firstWeekEnd);
        $this->processWeekPayrollDetails($payrollId, $userId, $secondWeekAttendanceDetails, $publicHolidays, $secondWeekStart, $secondWeekEnd);
    }

    protected function processWeekPayrollDetails($payrollId, $userId, $attendanceDetails, $publicHolidays, $weekStart, $weekEnd)
    {
        foreach ($attendanceDetails as $attendanceDate => $attendanceDetail) {
            foreach ($attendanceDetail as $attendanceForDay) {
                $guardTypeId = $attendanceForDay['guard_type_id'];
                $clientSiteId = $attendanceForDay['client_site_id'];
                $previousRecords = PayrollDetail::where('payroll_id', $payrollId)->where('guard_id', $userId)->where('date', $attendanceDate)->get();

                $previousNormalMinutes = 0;
                foreach ($previousRecords as $record) {
                    $previousNormalMinutes += $record->normal_hours * 60;
                }

                $weeklyPreviousRecords = PayrollDetail::where('payroll_id', $payrollId)->where('guard_id', $userId)->whereBetween('date', [$weekStart, $weekEnd])->sum('normal_hours');

                $existingRecord = PayrollDetail::where('payroll_id', $payrollId)->where('guard_id', $userId)->where('guard_type_id', $guardTypeId)->where('date', $attendanceDate)->first();

                $inTime = Carbon::parse($attendanceForDay['in_time']);
                $outTime = Carbon::parse($attendanceForDay['out_time']);
                $workedMinutes = $inTime->diffInMinutes($outTime);
                if ($attendanceForDay['late_min'] != 0) {
                    $workedMinutes = $workedMinutes + $attendanceForDay['late_min'];
                }

                /* $weekllyHourlyMin = 40 * 60;
                // $regularWorkingHoursPerDay = 8 * 60;
                // if ($workedMinutes >= 465 && $workedMinutes <= $regularWorkingHoursPerDay) {
                //     $workedMinutes = $regularWorkingHoursPerDay;
                // }

                $dayOfWeek = Carbon::parse($attendanceDate)->dayOfWeek;
                if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) {
                    $regularMinutes = 0;
                    $overtimeMinutes = $workedMinutes;
                    $publicHolidayMinutes = 0;
                } else {
                    $regularMinutes = 0;
                    $overtimeMinutes = 0;
                    $publicHolidayMinutes = 0;

                    $isPublicHoliday = in_array($attendanceDate, $publicHolidays);

                    if ($isPublicHoliday) {
                        $publicHolidayMinutes = $workedMinutes;
                    } else {
                        $remainingNormalMinutes = max(0, $weekllyHourlyMin - ($weeklyPreviousRecords * 60));
                        if ($workedMinutes <= $remainingNormalMinutes) {
                            $regularMinutes = $workedMinutes;
                        } else {
                            $regularMinutes = $remainingNormalMinutes;
                            $overtimeMinutes = $workedMinutes - $remainingNormalMinutes;
                        }
                    }
                }*/

                $weeklyHourlyMin = 40 * 60; // 40 hours in minutes
                $dayOfWeek = Carbon::parse($attendanceDate)->dayOfWeek;
                $isPublicHoliday = in_array($attendanceDate, $publicHolidays);

                $regularMinutes = 0;
                $overtimeMinutes = 0;
                $publicHolidayMinutes = 0;

                if ($isPublicHoliday) {
                    $publicHolidayMinutes = $workedMinutes;
                } else {
                    $remainingNormalMinutes = max(0, $weeklyHourlyMin - ($weeklyPreviousRecords * 60));

                    if ($workedMinutes <= $remainingNormalMinutes) {
                        $regularMinutes = $workedMinutes;
                    } else {
                        if ($remainingNormalMinutes > 0) {
                            $regularMinutes = $remainingNormalMinutes;
                            $overtimeMinutes = $workedMinutes - $remainingNormalMinutes;
                        } else {
                            $overtimeMinutes = $workedMinutes;
                        }
                    }
                }

                $regularHours = $this->convertToHoursAndMinutes($regularMinutes);
                $overtimeHours = $this->convertToHoursAndMinutes($overtimeMinutes);
                $publicHolidayHours = $this->convertToHoursAndMinutes($publicHolidayMinutes);

                $regularHours = round((float)$regularHours, 2);
                $overtimeHours = round((float)$overtimeHours, 2);
                $publicHolidayHours = round((float)$publicHolidayHours, 2);

                $normalRate = $attendanceForDay['gross_hourly_rate'];
                $overtimeRate = $attendanceForDay['overtime_rate'];
                $publicHolidayRate = $attendanceForDay['holiday_rate'];

                $normalEarnings = $regularHours * $normalRate;
                $overtimeEarnings = $overtimeHours * $overtimeRate;
                $publicHolidayEarnings = $publicHolidayHours * $publicHolidayRate;

                $normalEarnings = round($normalEarnings, 2);
                $overtimeEarnings = round($overtimeEarnings, 2);
                $publicHolidayEarnings = round($publicHolidayEarnings, 2);

                if ($existingRecord) {
                    $existingRecord->normal_hours += $regularHours;
                    $existingRecord->overtime += $overtimeHours;
                    $existingRecord->public_holiday += $publicHolidayHours;
                    $existingRecord->normal_hours_rate += $normalEarnings;
                    $existingRecord->overtime_rate += $overtimeEarnings;
                    $existingRecord->public_holiday_rate += $publicHolidayEarnings;
                    $existingRecord->save();
                } else {
                    $client = ClientSite::where('id', $clientSiteId)->first();
                    PayrollDetail::create([
                        'payroll_id' => $payrollId,
                        'guard_id' => $userId,
                        'guard_type_id' => $guardTypeId,
                        'client_id' => $client->client_id,
                        'client_site_id' => $clientSiteId,
                        'date' => $attendanceDate,
                        'normal_hours' => $regularHours,
                        'overtime' => $overtimeHours,
                        'public_holiday' => $publicHolidayHours,
                        'normal_hours_rate' => $normalEarnings,
                        'overtime_rate' => $overtimeEarnings,
                        'public_holiday_rate' => $publicHolidayEarnings,
                    ]);
                }
            }
        }
    }

    public function calculatePayrollUserHours($payrollId, $userId, $attendanceDetails, $publicHolidays, $previousStartDate, $previousEndDate)
    {
        $payrollDetails = PayrollDetail::with('guardType')->where('payroll_id', $payrollId)->where('guard_id', $userId)->whereBetween('date', [$previousStartDate, $previousEndDate])->get();
        $totalNormalHours = 0;
        $totalOvertimeHours = 0;
        $totalPublicHoliday = 0;
        $totalNormalEarnings = 0;
        $totalOvertimeEarnings = 0;
        $totalPublicHolidayEarnings = 0;
        $totalGrossSalaryEarned = 0;

        foreach ($payrollDetails as $detail) {
            $totalNormalHours += $detail->normal_hours;
            $totalOvertimeHours += $detail->overtime;
            $totalPublicHoliday += $detail->public_holiday;

            $totalNormalEarnings += $detail->normal_hours_rate;
            $totalOvertimeEarnings += $detail->overtime_rate;
            $totalPublicHolidayEarnings += $detail->public_holiday_rate;
        }

        $userData = User::with('guardAdditionalInformation', 'guardAdditionalInformation.rateMaster')->where('id', $userId)->first();
        list($leavePaid, $leaveNotPaid, $paidLeaveBalance, $leaveEncashmentAmount) = $this->calculateLeaveDetails($userId, $previousStartDate, $previousEndDate);
        $pendingLeaveAmount = 0;
        $paidLeaveAmount = 0;
        $notPaidLeaveAmount = 0;
        if ($leavePaid > 0) {
            $totalNormalHours += $leavePaid * 8;
            $totalNormalEarnings += (($leavePaid * 8) * $userData->guardAdditionalInformation->rateMaster->gross_hourly_rate);
        }
        if ($leaveNotPaid > 0) {
            $totalNormalHours -= $leaveNotPaid * 8;
            $totalNormalEarnings -= (($leaveNotPaid * 8) * $userData->guardAdditionalInformation->rateMaster->gross_hourly_rate);
        }
        if ($paidLeaveBalance > 0) {
            $pendingLeaveAmount += (($paidLeaveBalance * 8) * $userData->guardAdditionalInformation->rateMaster->gross_hourly_rate);
        }

        $leavesAmount = $pendingLeaveAmount;

        $totalGrossSalaryEarned = $totalNormalEarnings + $leavesAmount + $totalOvertimeEarnings + $totalPublicHolidayEarnings + $leaveEncashmentAmount;

        $approvedPensionScheme = 0;
        $dateOfBirth = $userData->guardAdditionalInformation->date_of_birth;
        $birthDate = Carbon::parse($dateOfBirth);
        $age = $birthDate->age;

        $currentYear = Carbon::now()->year;
        $fullYearNis = Payroll::where('guard_id', $userId)->whereYear('created_at', $currentYear)->get();

        $lessNis = 0;
        $employerContributionNis = 0;

        if ($userData->is_statutory == 0) {
            $totalNisForCurrentYear = $fullYearNis->sum('less_nis');
            if ($age >= 70) {
                $lessNis = 0;
                $employerContributionNis = 0;
            } else {
                if ($totalNisForCurrentYear < 150000) {
                    $nisDeduction = $totalGrossSalaryEarned * 0.03;
                    $employerContributionNis = $totalGrossSalaryEarned * 0.03;
                    $remainingNisToReachLimit = 150000 - $totalNisForCurrentYear;
                    if ($nisDeduction > $remainingNisToReachLimit) {
                        $lessNis = $remainingNisToReachLimit;
                    } else {
                        $lessNis = $nisDeduction;
                    }
                } else {
                    $lessNis = 0;
                    $employerContributionNis = 0;
                }
                // $employerContributionNis = $totalGrossSalaryEarned * 0.03;
            }
            /*if ($statutoryIncome < 65389) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > 65389 && $statutoryIncome <= 230769.23) {
                $payeData = $statutoryIncome - 65389;
                $payeIncome = $payeData * 0.25;
            } elseif ($statutoryIncome > 230770.23) {
                $payeData = $statutoryIncome - 230770.23;
                $payeIncome = $payeData * 0.30;
            }*/

            /* $statutoryIncome  = $totalGrossSalaryEarned -  $lessNis - $approvedPensionScheme;
            // Determine threshold based on age
            if ($age >= 65) {
                $taxFreeThreshold = 75003.38; // Higher threshold for age 65+
            } else {
                $taxFreeThreshold = 65389;
            }

            if ($statutoryIncome < $taxFreeThreshold) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > $taxFreeThreshold && $statutoryIncome <= 230769.23) {
                $payeData = $statutoryIncome - $taxFreeThreshold;
                $payeIncome = $payeData * 0.25;
            } elseif ($statutoryIncome > 230770.23) {
                $payeData = $statutoryIncome - 230770.23;
                $payeIncome = $payeData * 0.30;
            }*/

            $statutoryIncome = $totalGrossSalaryEarned - $lessNis - $approvedPensionScheme;
            $currentDate = Carbon::now();

            $applicableThreshold = GuardTaxThreshold::whereDate('effective_date', '<=', $currentDate)
                ->orderBy('effective_date', 'desc')
                ->first();

            if (!$applicableThreshold) {
                $applicableThreshold = GuardTaxThreshold::orderBy('effective_date', 'asc')->first();
            }

            if (!$applicableThreshold) {
                throw new \Exception('No guard tax threshold found. Please configure one.');
            }

            if ($age >= 65) {
                $annualWithBonus = $applicableThreshold->annual + 250000;
                $taxFreeThreshold = round($annualWithBonus / 26, 2); // 26 fortnights in a year
            } else {
                $taxFreeThreshold = $applicableThreshold->fortnightly;
            }

            // 6,000,000 annual => 6,000,000 / 26 ≈ 230769.23
            $nextSlabFortnightly = 230769.23;
            if ($statutoryIncome < $taxFreeThreshold) {
                $payeIncome = 0;
            } elseif ($statutoryIncome <= $nextSlabFortnightly) {
                $payeData = $statutoryIncome - $taxFreeThreshold;
                $payeIncome = $payeData * 0.25;
            } else {
                $payeData = ($statutoryIncome - $nextSlabFortnightly) * 0.30;
                $payeeThreshold = ($nextSlabFortnightly - $taxFreeThreshold) * 0.25;
                $payeIncome = $payeData + $payeeThreshold;
            }

            if ($age >= 65) {
                $eduction_tax = 0;
                $employer_contribution = 0;
                $nhtDeduction = 0;
                $employerContributionNht = 0;
            } else {
                $nhtDeduction = $totalGrossSalaryEarned * 0.02;
                $employerContributionNht =  $totalGrossSalaryEarned * 0.03;
                $eduction_tax = $statutoryIncome * 0.0225;
                $employer_contribution = $totalGrossSalaryEarned * 0.035;
            }

            $hearttax = $totalGrossSalaryEarned * 0.03;
        } else {
            $statutoryIncome  = $totalGrossSalaryEarned -  $lessNis - $approvedPensionScheme;
        }

        // if ($userData->is_statutory == 1) {
        $deductionTypes = [
            'Staff Loan'        => 'pending_staff_loan',
            'Medical Ins'       => 'pending_medical_insurance',
            'Salary Advance'    => 'pending_salary_advance',
            'PSRA'              => 'pending_psra',
            'Bank Loan'         => 'pending_bank_loan',
            'Approved Pension'  => 'pending_approved_pension',
            'Garnishment'       => 'pending_garnishment',
            'Missing Goods'     => 'pending_missing_goods',
            'Damaged Goods'     => 'pending_damaged_goods'
        ];

        $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);
        $pendingAmounts = array_fill_keys(array_keys($deductionTypes), 0);

        /*foreach ($deductionTypes as $deductionType => $pendingField) {
                $deductionRecords = Deduction::where('guard_id', $userId)->where('type', $deductionType)
                    ->whereDate('start_date', '<=', $previousEndDate)->whereDate('end_date', '>=', $previousStartDate)->get();

                foreach ($deductionRecords as $deduction) {
                    if ($deduction->start_date <= $previousEndDate && $deduction->end_date >= $previousStartDate) {
                        $totalDeductions[$deductionType] = $deduction->one_installment;
                        $pendingBalance = $deduction->pending_balance - $deduction->one_installment;
                        $pendingAmounts[$deductionType] = $deduction->pending_balance - $deduction->one_installment;
                        $deduction->update([
                            'pending_balance' => $pendingBalance
                        ]);

                        DeductionDetail::create([
                            'guard_id'        => $userId,
                            'deduction_id'    => $deduction->id,
                            'deduction_date'  => Carbon::now(),
                            'amount_deducted' => $deduction->one_installment,
                            'balance'         =>  $pendingBalance
                        ]);
                    }
                }
            }*/
        foreach ($deductionTypes as $deductionType => $pendingField) {
            $deductionRecords = Deduction::where('guard_id', $userId)
                ->where('type', $deductionType)
                ->where(function ($query) use ($previousStartDate, $previousEndDate) {
                    $query->whereNull('end_date') // Include deductions with no end_date
                        ->orWhere(function ($subQuery) use ($previousStartDate, $previousEndDate) {
                            $subQuery->whereDate('start_date', '<=', $previousEndDate)
                                ->whereDate('end_date', '>=', $previousStartDate);
                        });
                })->get();

            foreach ($deductionRecords as $deduction) {
                $shouldDeduct = true;

                // If end_date is set, respect the date range
                if ($deduction->end_date && $deduction->start_date > $previousEndDate || $deduction->end_date < $previousStartDate) {
                    $shouldDeduct = false;
                }

                if ($shouldDeduct && $deduction->pending_balance > 0) {
                    // Deduct full amount if end_date and no_of_payroll are not set
                    $installmentAmount = ($deduction->end_date && $deduction->no_of_payroll)
                        ? $deduction->one_installment
                        : $deduction->pending_balance;

                    $newPendingBalance = $deduction->pending_balance - $installmentAmount;
                    $totalDeductions[$deductionType] += $installmentAmount;
                    $pendingAmounts[$deductionType] = $newPendingBalance;
                    $deduction->update([
                        'pending_balance' => $newPendingBalance
                    ]);

                    DeductionDetail::create([
                        'guard_id'        => $userId,
                        'deduction_id'    => $deduction->id,
                        'deduction_date'  => Carbon::now(),
                        'amount_deducted' => $installmentAmount,
                        'balance'         => $newPendingBalance
                    ]);
                }
            }
        }

        $staffLoan = $totalDeductions['Staff Loan'];
        $medicalInsurance = $totalDeductions['Medical Ins'];
        $salaryAdvance = $totalDeductions['Salary Advance'];
        $psra = $totalDeductions['PSRA'];
        $bankLoan = $totalDeductions['Bank Loan'];
        $approvedPension = $totalDeductions['Approved Pension'];
        $garnishment = $totalDeductions['Garnishment'];
        $missingGoods  = $totalDeductions['Missing Goods'];
        $damagedGoods = $totalDeductions['Damaged Goods'];

        $pendingStaffLoan = $pendingAmounts['Staff Loan'];
        $pendingMedicalInsurance = $pendingAmounts['Medical Ins'];
        $pendingSalaryAdvance = $pendingAmounts['Salary Advance'];
        $pendingPsra = $pendingAmounts['PSRA'];
        $pendingBankLoan = $pendingAmounts['Bank Loan'];
        $pendingApprovedPension = $pendingAmounts['Approved Pension'];
        $pendingGarnishment = $pendingAmounts['Garnishment'];
        $pendingMissingGoods = $pendingAmounts['Missing Goods'];
        $pendingDamagedGoods = $pendingAmounts['Damaged Goods'];
        // }
        $threshold = 0;

        $payroll = Payroll::where('id', $payrollId)->update([
            'leave_paid'            => $leavePaid,
            'leave_not_paid'        => $leaveNotPaid,
            'pending_leave_balance' => $paidLeaveBalance,
            'paid_leaves_amount'    => (($leavePaid * 8) * $userData->guardAdditionalInformation->rateMaster->gross_hourly_rate),
            'not_paid_leaves_amount' => (($leaveNotPaid * 8) * $userData->guardAdditionalInformation->rateMaster->gross_hourly_rate),
            'pending_leaves_amount' => $pendingLeaveAmount,
            'normal_hours'          => $totalNormalHours,
            'overtime'              => $totalOvertimeHours,
            'public_holidays' => $totalPublicHoliday,
            'normal_hours_rate' => number_format($totalNormalEarnings, 2, '.', ''),
            'overtime_rate' =>  number_format($totalOvertimeEarnings, 2, '.', ''),
            'public_holiday_rate' =>  number_format($totalPublicHolidayEarnings, 2, '.', ''),
            'gross_salary_earned' => number_format($totalGrossSalaryEarned, 2, '.', ''),
            'less_nis' => number_format($lessNis, 2, '.', ''),
            'employer_contribution_nis_tax' => number_format($employerContributionNis ?? 0, 2, '.', ''),
            'approved_pension_scheme' => number_format($approvedPensionScheme, 2, '.', ''),
            'statutory_income' => number_format($statutoryIncome, 2, '.', ''),
            'education_tax' => number_format($eduction_tax ?? 0, 2, '.', ''),
            'employer_eduction_tax' => number_format($employer_contribution ?? 0, 2, '.', ''),
            'nht' => number_format($nhtDeduction ?? 0, 2, '.', ''),
            'employer_contribution_nht_tax' => number_format($employerContributionNht ?? 0, 2, '.', ''),
            'paye' => number_format($payeIncome ?? 0, 2, '.', ''),
            'staff_loan' => number_format($staffLoan ?? 0, 2, '.', ''),
            'medical_insurance' => number_format($medicalInsurance ?? 0, 2, '.', ''),
            'salary_advance' => number_format($salaryAdvance ?? 0, 2, '.', ''),
            'psra' => number_format($psra ?? 0, 2, '.', ''),
            'bank_loan' => number_format($bankLoan ?? 0, 2, '.', ''),
            // 'approved_pension' => number_format($approvedPension, 2, '.', ''),
            'threshold' => number_format($threshold, 2, '.', ''),
            'heart' => number_format($hearttax ?? 0, 2, '.', ''),
            'pending_staff_loan' => number_format($pendingStaffLoan ?? 0, 2, '.', ''),
            'pending_medical_insurance' => number_format($pendingMedicalInsurance ?? 0, 2, '.', ''),
            'pending_salary_advance' => number_format($pendingSalaryAdvance ?? 0, 2, '.', ''),
            'pending_psra' => number_format($pendingPsra ?? 0, 2, '.', ''),
            'pending_bank_loan' => number_format($pendingBankLoan ?? 0, 2, '.', ''),
            'pending_approved_pension' => number_format($pendingApprovedPension ?? 0, 2, '.', ''),
            'garnishment' => number_format($garnishment ?? 0, 2, '.', ''),
            'missing_goods' => number_format($missingGoods ?? 0, 2, '.', ''),
            'damaged_goods' => number_format($damagedGoods ?? 0, 2, '.', ''),
            'pending_garnishment' => number_format($pendingGarnishment ?? 0, 2, '.', ''),
            'pending_missing_goods' => number_format($pendingMissingGoods ?? 0, 2, '.', ''),
            'pending_damaged_goods' => number_format($pendingDamagedGoods ?? 0, 2, '.', ''),
        ]);
    }

    private function convertToHoursAndMinutes($minutes)
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $fractionalPart = $remainingMinutes / 60;

        return sprintf('%d.%02d', $hours, round($fractionalPart * 100));
    }

    protected function calculateLeaveDetails($userId, $previousStartDate, $previousEndDate)
    {
        $leavePaid = 0;
        $leaveNotPaid = 0;

        $paidLeaveBalance = 0;
        $baseYearlyLimit = (int) setting('yearly_leaves') ?: 10;
        $year = Carbon::parse($previousStartDate)->year;
        $previousYear = $year - 1;
        // Calculate leaves taken last year
        $usedLeavesLastYear = Leave::where('guard_id', $userId)
            ->where('status', 'Approved')
            ->whereYear('date', $previousYear)
            ->count();
        // Carry forward logic
        $carryForwardLeaves = max(0, $baseYearlyLimit - $usedLeavesLastYear);
        $carryForwardLimit = 10;
        $carryForwardLeaves = min($carryForwardLeaves, $carryForwardLimit);
        $paidLeaveBalanceLimit = $baseYearlyLimit + $carryForwardLeaves;
        $lastDayOfDecember = Carbon::createFromDate($year, 12, 31);
        $leavesQuery = Leave::where('guard_id', $userId)->where('status', 'Approved');
        $leavesCountInDecember = $leavesQuery->whereYear('date', $lastDayOfDecember->year)->count();
        if ($lastDayOfDecember->between($previousStartDate, $previousEndDate)) {
            $paidLeaveBalance = max(0, $paidLeaveBalanceLimit - $leavesCountInDecember);
        }

        $leavesCount = $leavesQuery->whereBetween('date', [$previousStartDate, $previousEndDate])->count();
        if ($leavesCount > 0) {
            $approvedLeaves = Leave::where('guard_id', $userId)->where('status', 'Approved')->whereDate('date', '<', $previousStartDate)->count();
            $totalApprovedLeaves = $leavesCount + $approvedLeaves;
            if ($totalApprovedLeaves > $paidLeaveBalanceLimit) {
                $excessLeaves = max(0, $totalApprovedLeaves - $paidLeaveBalanceLimit);

                if ($excessLeaves > 0) {
                    if ($leavesCount > $excessLeaves) {
                        $leaveNotPaid = $excessLeaves;
                        $leavePaid = max(0, $leavesCount - $leaveNotPaid);
                    } else {
                        $leaveNotPaid = $leavesCount;
                        $leavePaid = 0;
                    }
                } else {
                    $leaveNotPaid = 0;
                    $leavePaid = $leavesCount;
                }
            } else {
                $leavePaid = $leavesCount;
                $leaveNotPaid = 0;
            }
        }

        $leaveEncashmentAmount = 0;
        // Calculate encashed leaves and amount
        $leaveEncashments = GuardLeaveEncashment::where('guard_id', $userId)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->get();

        if ($leaveEncashments->count()) {
            $hourlyRate = User::with('guardAdditionalInformation.rateMaster')
                ->find($userId)
                ->guardAdditionalInformation
                ->rateMaster
                ->gross_hourly_rate;

            foreach ($leaveEncashments as $encashment) {
                $leaveEncashmentAmount += ($encashment->encash_leaves * 8) * $hourlyRate;
            }
        }

        return [$leavePaid, $leaveNotPaid, $paidLeaveBalance, $leaveEncashmentAmount];
    }

    // protected function calculateLeaveDetails($userId, $previousStartDate, $previousEndDate)
    // {
    //     $leavePaid = 0;
    //     $leaveNotPaid = 0;
    //     $paidLeaveBalance = 0;

    //     // Limits
    //     $baseSickLeaveLimit = (int) setting('yearly_leaves') ?: 10;
    //     $vacationLeaveLimit = (int) setting('vacation_leaves') ?: 10;
    //     $maternityLeaveLimit = (int) setting('maternity_leaves') ?: 44;

    //     $year = Carbon::parse($previousStartDate)->year;
    //     $previousYear = $year - 1;

    //     // Carry Forward Sick Leaves
    //     $usedSickLeavesLastYear = Leave::where('guard_id', $userId)
    //         ->where('status', 'Approved')
    //         ->where('leave_type', 'Sick Leave')
    //         ->whereYear('date', $previousYear)
    //         ->count();

    //     $carryForwardLimit = 10;
    //     $carryForwardSick = min(max(0, $baseSickLeaveLimit - $usedSickLeavesLastYear), $carryForwardLimit);
    //     $sickLeaveBalance = $baseSickLeaveLimit + $carryForwardSick;

    //     $vacationLeaveBalance = 0;
    //     $maternityLeaveBalance = $maternityLeaveLimit;

    //     // Add Vacation Leave only on anniversary
    //     $joiningDate = optional(User::find($userId)->guardAdditionalInformation)->joining_date;
    //     if ($joiningDate) {
    //         $joiningDate = Carbon::parse($joiningDate);
    //         $anniversary = $joiningDate->copy()->year($year);
    //         if ($anniversary->lt($joiningDate)) $anniversary->addYear();

    //         if ($anniversary->between($previousStartDate, $previousEndDate)) {
    //             $vacationLeaveBalance = $vacationLeaveLimit;
    //         }
    //     }

    //     // Fetch approved leaves in this period
    //     $leavesInRange = Leave::where('guard_id', $userId)
    //         ->where('status', 'Approved')
    //         ->whereBetween('date', [$previousStartDate, $previousEndDate])
    //         ->get();

    //     // Count by type
    //     $leaveCounts = [
    //         'Sick Leave' => 0,
    //         'Vacation Leave' => 0,
    //         'Maternity Leave' => 0,
    //     ];

    //     foreach ($leavesInRange as $leave) {
    //         $units = ($leave->type === 'Half Day') ? 0.5 : 1;
    //         if (isset($leaveCounts[$leave->leave_type])) {
    //             $leaveCounts[$leave->leave_type] += $units;
    //         }
    //     }

    //     $unpaidLeaves = 0;

    //     foreach ($leaveCounts as $type => $count) {
    //         switch ($type) {
    //             case 'Sick Leave':
    //                 if ($count > $sickLeaveBalance) {
    //                     $unpaidLeaves += ($count - $sickLeaveBalance);
    //                     $leavePaid += $sickLeaveBalance;
    //                     $sickLeaveBalance = 0;
    //                 } else {
    //                     $leavePaid += $count;
    //                     $sickLeaveBalance -= $count;
    //                 }
    //                 break;

    //             case 'Vacation Leave':
    //                 if ($count > $vacationLeaveBalance) {
    //                     $unpaidLeaves += ($count - $vacationLeaveBalance);
    //                     $leavePaid += $vacationLeaveBalance;
    //                     $vacationLeaveBalance = 0;
    //                 } else {
    //                     $leavePaid += $count;
    //                     $vacationLeaveBalance -= $count;
    //                 }
    //                 break;

    //             case 'Maternity Leave':
    //                 if ($count > $maternityLeaveBalance) {
    //                     $unpaidLeaves += ($count - $maternityLeaveBalance);
    //                     $leavePaid += $maternityLeaveBalance;
    //                     $maternityLeaveBalance = 0;
    //                 } else {
    //                     $leavePaid += $count;
    //                     $maternityLeaveBalance -= $count;
    //                 }
    //                 break;
    //         }
    //     }

    //     $leaveNotPaid = min($unpaidLeaves, 22); // or your business logic
    //     $paidLeaveBalance = $sickLeaveBalance + $vacationLeaveBalance + $maternityLeaveBalance;

    //     // Leave Encashment Calculation
    //     $leaveEncashmentAmount = 0;
    //     $leaveEncashments = GuardLeaveEncashment::where('guard_id', $userId)
    //         ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
    //         ->get();

    //     if ($leaveEncashments->count()) {
    //         $hourlyRate = optional(optional(User::with('guardAdditionalInformation.rateMaster')->find($userId))->guardAdditionalInformation->rateMaster)->gross_hourly_rate;

    //         foreach ($leaveEncashments as $encashment) {
    //             $leaveEncashmentAmount += ($encashment->encash_leaves * 8) * $hourlyRate;
    //         }
    //     }

    //     return [$leavePaid, $leaveNotPaid, $paidLeaveBalance, $leaveEncashmentAmount];
    // }

    private function generateInvoice($payrollDetails, $startDate, $endDate)
    {
        $aggregatedData = $payrollDetails->groupBy('client_site_id')->map(function ($clientGroup, $clientSiteId) {
            return $clientGroup->groupBy('guard_type_id')->map(function ($guardGroup, $guardTypeId) use ($clientSiteId) {
                return [
                    'client_site_id' => $clientSiteId,
                    'guard_type_id' => $guardTypeId,
                    'dates' => $guardGroup->groupBy('date'),
                ];
            });
        });

        $invoiceDetails = [];

        foreach ($aggregatedData as $clientSiteId => $clientData) {
            $existingInvoice = Invoice::where('client_site_id', $clientSiteId)
                ->where('start_date', Carbon::parse($startDate)->format('Y-m-d'))
                ->where('end_date', Carbon::parse($endDate)->format('Y-m-d'))->first();

            if ($existingInvoice) {
                $invoice = $existingInvoice;
            } else {
                $invoiceCode = $this->generateInvoiceCode();
                $invoice = Invoice::create([
                    'invoice_code' => $invoiceCode,
                    'client_site_id' => $clientSiteId,
                    'invoice_date' => Carbon::now()->format('Y-m-d'),
                    'start_date' => Carbon::parse($startDate)->format('Y-m-d'),
                    'end_date' => Carbon::parse($endDate)->format('Y-m-d'),
                ]);
            }

            if ($invoice) {
                foreach ($clientData as $guardTypeId => $dateData) {
                    $rate = RateMaster::find($guardTypeId);
                    if (!$rate) continue;

                    foreach ($dateData['dates'] as $date => $guardData) {
                        $noOfGuards = $guardData->pluck('guard_id')->unique()->count() ?? 0;

                        $normalHours = $guardData->sum('normal_hours');
                        if ($normalHours > 0) {
                            $totalAmount = $normalHours * ($rate->gross_hourly_rate ?? 0);
                            $existingDetail = InvoiceDetail::where('invoice_id', $invoice->id)->where('guard_type_id', $guardTypeId)->where('hours_type', 'Normal')->where('date', Carbon::parse($date)->format('Y-m-d'))->exists();

                            if (!$existingDetail) {
                                $invoiceDetails[] = [
                                    'invoice_id' => $invoice->id,
                                    'guard_type_id' => $guardTypeId,
                                    'hours_type' => 'Normal',
                                    'date' => $date,
                                    'rate' => $rate->gross_hourly_rate,
                                    'no_of_guards' => $noOfGuards,
                                    'total_hours' => number_format($normalHours, 2),
                                    'invoice_amount' => $totalAmount,
                                ];
                            }
                        }

                        $overtimeHours = $guardData->sum('overtime');
                        if ($overtimeHours > 0) {
                            $totalAmount = $noOfGuards * $overtimeHours * ($rate->overtime_rate ?? 0);
                            $existingDetail = InvoiceDetail::where('invoice_id', $invoice->id)->where('guard_type_id', $guardTypeId)->where('hours_type', 'Overtime')->where('date', Carbon::parse($date)->format('Y-m-d'))->exists();

                            if (!$existingDetail) {
                                $invoiceDetails[] = [
                                    'invoice_id' => $invoice->id,
                                    'guard_type_id' => $guardTypeId,
                                    'hours_type' => 'Overtime',
                                    'date' => $date,
                                    'rate' => $rate->overtime_rate,
                                    'no_of_guards' => $noOfGuards,
                                    'total_hours' => $overtimeHours,
                                    'invoice_amount' => $totalAmount,
                                ];
                            }
                        }

                        $publicHolidayHours = $guardData->sum('public_holiday');
                        if ($publicHolidayHours > 0) {
                            $totalAmount = $noOfGuards * $publicHolidayHours * ($rate->holiday_rate ?? 0);
                            $existingDetail = InvoiceDetail::where('invoice_id', $invoice->id)->where('guard_type_id', $guardTypeId)->where('hours_type', 'Public Holiday')->where('date', Carbon::parse($date)->format('Y-m-d'))->exists();

                            if (!$existingDetail) {
                                $invoiceDetails[] = [
                                    'invoice_id' => $invoice->id,
                                    'guard_type_id' => $guardTypeId,
                                    'hours_type' => 'Public Holiday',
                                    'date' => $date,
                                    'rate' => $rate->holiday_rate,
                                    'no_of_guards' => $noOfGuards,
                                    'total_hours' => $publicHolidayHours,
                                    'invoice_amount' => $totalAmount,
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (!empty($invoiceDetails)) {
            InvoiceDetail::insert($invoiceDetails);
            foreach ($invoiceDetails as $detail) {
                $totalAmount = InvoiceDetail::where('invoice_id', $detail['invoice_id'])->sum('invoice_amount');
                $invoice = Invoice::find($detail['invoice_id']);
                if ($invoice) {
                    $invoice->total_amount = $totalAmount;
                    $invoice->save();
                }
            }
        }
    }

    private function generateInvoiceCode()
    {
        $lastInvoice = Invoice::orderBy('invoice_code', 'desc')->first();

        if (!$lastInvoice) {
            return 'S-25-000001';
        }

        preg_match('/S-25-(\d+)/', $lastInvoice->invoice_code, $matches);
        $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
        $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT); // Pad the number to 6 digits

        return 'S-25-' . $newNumber;
    }
}
