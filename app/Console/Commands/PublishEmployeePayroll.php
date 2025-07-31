<?php

namespace App\Console\Commands;

use App\Models\EmployeeDeduction;
use App\Models\EmployeeDeductionDetail;
use App\Models\EmployeeLeave;
use App\Models\EmployeeOvertime;
use App\Models\EmployeeRateMaster;
use App\Models\User;
use App\Models\EmployeePayroll;
use App\Models\LeaveEncashment;
use App\Models\PublicHoliday;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\EmployeeTaxThreshold;
use App\Models\TwentyTwoDayInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublishEmployeePayroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:employee-payroll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Employee Payroll';

    /**
     * Execute the console command.
     */

    /*public function handle()
    {
        $userRole = Role::where('id', 9)->first();
        $employees = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with('guardAdditionalInformation')->latest()->get();

        if ($employees) {
            foreach ($employees as $employee) {
                $today = Carbon::now()->startOfDay();
                // $today = Carbon::parse('25-01-2025')->startOfDay(); //--Manual CheckSS
                $twentyTwoDay = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
                if ($twentyTwoDay) {
                    $endDate = Carbon::parse($twentyTwoDay->end_date)->startOfDay();
                    $startDate = Carbon::parse($twentyTwoDay->start_date)->startOfDay();

                    if ($today->month == 12) {
                        $year = Carbon::parse($today)->year;
                        $previousEndDate = Carbon::create($year, 12, 13);
                    } else {
                        $previousEndDate = Carbon::parse($startDate)->addDays(24);  // Payroll generates 25-day period
                    }
                    $previousStartDate = $startDate;
                    if ($previousEndDate == $today) {
                        $joiningDate = Carbon::parse($employee->guardAdditionalInformation->date_of_joining);
                        $startDateForEmployee = $joiningDate > $previousStartDate ? $joiningDate : $previousStartDate;
                        $leavingDateOfEmployee = $employee->guardAdditionalInformation->date_of_seperation ? Carbon::parse($employee->guardAdditionalInformation->date_of_seperation) : null;
                        if ($leavingDateOfEmployee && $leavingDateOfEmployee->month < $today->month) {
                            continue;
                        }
                        if ($startDateForEmployee->greaterThan($endDate)) {
                            $normalDays = 0;
                        } else {
                            if ($joiningDate <= $previousStartDate) {
                                $normalDays = 22;
                                $workingDays = 0;
                                if ($leavingDateOfEmployee && $leavingDateOfEmployee->greaterThanOrEqualTo($previousStartDate) && $leavingDateOfEmployee->lessThanOrEqualTo($endDate)) {
                                    $endDate = $leavingDateOfEmployee;
                                    $currentDate = $previousStartDate->copy();
                                    while ($currentDate <= $endDate) {
                                        if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY && !$this->isPublicHoliday($currentDate)) {
                                            $workingDays++;
                                        }
                                        $currentDate->addDay();
                                    }
                                    $normalDays = $workingDays;
                                }
                            } else {
                                $workingDays = 0;
                                $currentDate = $startDateForEmployee;
                                while ($currentDate <= $endDate) {
                                    if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY && !$this->isPublicHoliday($currentDate)) {
                                        $workingDays++;
                                    }
                                    $currentDate->addDay();
                                }
                                $normalDays = $workingDays;
                            }
                        }

                        if ($normalDays > 0) {
                            $payroll = EmployeePayroll::where('employee_id', $employee->id)->whereDate('start_date', $previousStartDate)->whereDate('end_date', $endDate)->first();
                            if (!$payroll) {
                                $employeeRateMaster = EmployeeRateMaster::where('employee_id', $employee->id)->first();
                                if ($employeeRateMaster) {
                                    $yearlySalary = $employeeRateMaster->gross_salary;
                                    $daySalary = ($yearlySalary / 12) / 22;

                                    //===== Employee Leaves Calculation =====
                                    list($leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary, $pendingLeaveAmount, $normalDaysSalary) = $this->calculateLeaveDetails($normalDays, $employee, $previousStartDate, $endDate, $daySalary);
                                    //===== End Leaves Calculation =====

                                    //===== Employee Payroll Statutory Calculation =====
                                    $payrollStatutoryData = $this->calculateEmployeePayrollStatutory($employee, $previousStartDate, $endDate, $grossSalary, $daySalary);
                                    // ========End Payroll Statutory Calculation ========

                                    //===== Employee Payroll Non Statutory Calculation =====
                                    list($totalDeductions, $pendingAmounts) = $this->calculateNonStatutoryDeductions($employee, $previousStartDate, $endDate);
                                    //===== End Employee Payroll Non Statutory Calculation =====

                                    $payrollData = array_merge($payrollStatutoryData, [
                                        'employee_id' => $employee->id,
                                        'start_date' => Carbon::parse($twentyTwoDay->start_date)->startOfDay(),
                                        'end_date' => Carbon::parse($twentyTwoDay->end_date)->startOfDay(),
                                        'normal_days' => $normalDays,
                                        'leave_paid' => $leavePaid,
                                        'leave_not_paid' => $leaveNotPaid,
                                        'pending_leave_balance' => $paidLeaveBalance,
                                        'day_salary' => $daySalary,
                                        'normal_salary' => $normalDaysSalary,
                                        'pending_leave_amount' => $pendingLeaveAmount,
                                        'gross_salary' => $grossSalary,
                                        'staff_loan' => $totalDeductions['Staff Loan'],
                                        'medical_insurance' => $totalDeductions['Medical Ins'],
                                        'salary_advance' => $totalDeductions['Salary Advance'],
                                        'psra' => $totalDeductions['PSRA'],
                                        'bank_loan' => $totalDeductions['Bank Loan'],
                                        'pending_staff_loan' => $pendingAmounts['Staff Loan'],
                                        'pending_medical_insurance' => $pendingAmounts['Medical Ins'],
                                        'pending_salary_advance' => $pendingAmounts['Salary Advance'],
                                        'pending_psra' => $pendingAmounts['PSRA'],
                                        'pending_bank_loan' => $pendingAmounts['Bank Loan'],
                                        'pending_approved_pension' => $pendingAmounts['Approved Pension'],
                                        'garnishment' => $totalDeductions['Garnishment'],
                                        'missing_goods' => $totalDeductions['Missing Goods'],
                                        'damaged_goods' => $totalDeductions['Damaged Goods'],
                                        'pending_garnishment' => $pendingAmounts['Garnishment'],
                                        'pending_missing_goods' => $pendingAmounts['Missing Goods'],
                                        'pending_damaged_goods' => $pendingAmounts['Damaged Goods'],
                                        'is_publish' => 0,
                                    ]);

                                    if (EmployeePayroll::create($payrollData)) {
                                        echo "Employee payroll created successfully";
                                    } else {
                                        echo "Employee payroll not created";
                                    }
                                } else {
                                    echo "Employee ratemaster not created";
                                }
                            } else {
                                echo "Employee payroll already created";
                            }
                        } else {
                            echo "Employee joining days are 0.";
                        }
                    } else {
                        echo "Employee payroll dates not matched";
                    }
                }
            }
        } else {
            echo "No employee found";
        }
    }*/

    public function handle()
    {
        $userRole = Role::where('id', 9)->first();
        $employees = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with('guardAdditionalInformation')->latest()->get();

        $today = Carbon::now()->startOfDay();
        // $today = Carbon::parse('25-03-2025')->startOfDay(); // For Manuall testing 

        $processingDate = $this->getProcessingDate($today);

        if (!$today->equalTo($processingDate)) {
            $this->info("Today is not a valid payroll processing day. Processing should occur on: " . $processingDate->format('Y-m-d'));
            return;
        }

        if ($employees) {
            foreach ($employees as $employee) {
                $twentyTwoDay = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->first();

                if ($twentyTwoDay) {
                    $endDate = Carbon::parse($twentyTwoDay->end_date)->startOfDay();
                    $startDate = Carbon::parse($twentyTwoDay->start_date)->startOfDay();

                    if ($today->month == 12) {
                        $year = Carbon::parse($today)->year;
                        $previousEndDate = Carbon::create($year, 12, 13);
                    } else {
                        $previousEndDate = Carbon::parse($startDate)->addDays(24); // For 25-day period
                    }

                    $previousStartDate = $startDate;

                    // Modified to check if today matches the calculated payroll end date
                    if ($today->equalTo($previousEndDate) || $today->lessThan($previousEndDate) && $previousEndDate->day == 25) {
                        $joiningDate = Carbon::parse($employee->guardAdditionalInformation->date_of_joining);
                        $startDateForEmployee = $joiningDate > $previousStartDate ? $joiningDate : $previousStartDate;
                        $leavingDateOfEmployee = $employee->guardAdditionalInformation->date_of_seperation ?
                            Carbon::parse($employee->guardAdditionalInformation->date_of_seperation) : null;

                        if ($leavingDateOfEmployee && $leavingDateOfEmployee->month < $today->month) {
                            continue;
                        }

                        if ($startDateForEmployee->greaterThan($endDate)) {
                            $normalDays = 0;
                        } else {
                            if ($joiningDate <= $previousStartDate) {
                                $normalDays = 22;
                                $workingDays = 0;
                                if ($leavingDateOfEmployee && $leavingDateOfEmployee->greaterThanOrEqualTo($previousStartDate) && $leavingDateOfEmployee->lessThanOrEqualTo($endDate)) {
                                    $endDate = $leavingDateOfEmployee;
                                    $currentDate = $previousStartDate->copy();
                                    while ($currentDate <= $endDate) {
                                        if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY && !$this->isPublicHoliday($currentDate)) {
                                            $workingDays++;
                                        }
                                        $currentDate->addDay();
                                    }
                                    $normalDays = min($workingDays, 22);
                                }
                            } else {
                                $workingDays = 0;
                                $currentDate = $startDateForEmployee;
                                while ($currentDate <= $endDate) {
                                    if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY && !$this->isPublicHoliday($currentDate)) {
                                        $workingDays++;
                                    }
                                    $currentDate->addDay();
                                }
                                $normalDays = min($workingDays, 22);
                            }
                        }

                        if ($normalDays > 0) {
                            $payroll = EmployeePayroll::where('employee_id', $employee->id)
                                ->whereDate('start_date', $previousStartDate)
                                ->whereDate('end_date', $endDate)
                                ->first();

                            if (!$payroll) {
                                $employeeRateMaster = EmployeeRateMaster::where('employee_id', $employee->id)->first();
                                if ($employeeRateMaster) {
                                    $yearlySalary = $employeeRateMaster->gross_salary;
                                    $daySalary = ($yearlySalary / 12) / 22;

                                    //===== Employee Leaves Calculation =====
                                    list($leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary, $pendingLeaveAmount, $normalDaysSalary) =
                                        $this->calculateLeaveDetails($normalDays, $employee, $previousStartDate, $endDate, $daySalary);
                                    //===== End Leaves Calculation =====

                                    $overtimeTotal = EmployeeOvertime::where('employee_id', $employee->id)
                                        ->whereBetween('work_date', [$previousStartDate, $endDate])
                                        ->sum('overtime_income');

                                    $grossSalary += $overtimeTotal;
                                    //===== Employee Payroll Statutory Calculation =====
                                    $payrollStatutoryData = $this->calculateEmployeePayrollStatutory(
                                        $employee,
                                        $previousStartDate,
                                        $endDate,
                                        $grossSalary,
                                        $daySalary
                                    );
                                    // ========End Payroll Statutory Calculation ========

                                    //===== Employee Payroll Non Statutory Calculation =====
                                    list($totalDeductions, $pendingAmounts) = $this->calculateNonStatutoryDeductions(
                                        $employee,
                                        $previousStartDate,
                                        $endDate
                                    );
                                    // dd($totalDeductions);
                                    //===== End Employee Payroll Non Statutory Calculation =====

                                    $payrollData = array_merge($payrollStatutoryData, [
                                        'employee_id' => $employee->id,
                                        'start_date' => Carbon::parse($twentyTwoDay->start_date)->startOfDay(),
                                        'end_date' => Carbon::parse($twentyTwoDay->end_date)->startOfDay(),
                                        'normal_days' => $normalDays,
                                        'leave_paid' => $leavePaid,
                                        'leave_not_paid' => $leaveNotPaid,
                                        'pending_leave_balance' => $paidLeaveBalance,
                                        'day_salary' => $daySalary,
                                        'normal_salary' => $normalDaysSalary,
                                        'pending_leave_amount' => $pendingLeaveAmount,
                                        'gross_salary' => $grossSalary,
                                        'staff_loan' => $totalDeductions['Staff Loan'],
                                        'medical_insurance' => $totalDeductions['Medical Ins'],
                                        'salary_advance' => $totalDeductions['Salary Advance'],
                                        'psra' => $totalDeductions['PSRA'],
                                        'bank_loan' => $totalDeductions['Bank Loan'],
                                        'pending_staff_loan' => $pendingAmounts['Staff Loan'],
                                        'pending_medical_insurance' => $pendingAmounts['Medical Ins'],
                                        'pending_salary_advance' => $pendingAmounts['Salary Advance'],
                                        'pending_psra' => $pendingAmounts['PSRA'],
                                        'pending_bank_loan' => $pendingAmounts['Bank Loan'],
                                        'approved_pension_scheme' => $totalDeductions['Approved Pension'],
                                        'pending_approved_pension' => $pendingAmounts['Approved Pension'],
                                        'garnishment' => $totalDeductions['Garnishment'],
                                        'missing_goods' => $totalDeductions['Missing Goods'],
                                        'damaged_goods' => $totalDeductions['Damaged Goods'],
                                        'pending_garnishment' => $pendingAmounts['Garnishment'],
                                        'pending_missing_goods' => $pendingAmounts['Missing Goods'],
                                        'pending_damaged_goods' => $pendingAmounts['Damaged Goods'],
                                        // New deduction fields
                                        'ncb_loan' => $totalDeductions['NCB Loan'],
                                        'cwj_credit_union_loan' => $totalDeductions['C&WJ Credit Union Loan'],
                                        'edu_com_coop_loan' => $totalDeductions['Edu Com Co-op Loan'],
                                        'nht_mortgage_loan' => $totalDeductions['National Housing Trust Mortgage Loan'],
                                        'jn_bank_loan' => $totalDeductions['Jamaica National Bank Loan'],
                                        'sagicor_bank_loan' => $totalDeductions['Sagicor Bank Loan'],
                                        'health_insurance' => $totalDeductions['Health Insurance'],
                                        'life_insurance' => $totalDeductions['Life Insurance'],
                                        'overpayment' => $totalDeductions['Overpayment'],
                                        'training' => $totalDeductions['Training'],
                                        // Pending amounts for new deductions
                                        'pending_ncb_loan' => $pendingAmounts['NCB Loan'],
                                        'pending_cwj_credit_union_loan' => $pendingAmounts['C&WJ Credit Union Loan'],
                                        'pending_edu_com_coop_loan' => $pendingAmounts['Edu Com Co-op Loan'],
                                        'pending_nht_mortgage_loan' => $pendingAmounts['National Housing Trust Mortgage Loan'],
                                        'pending_jn_bank_loan' => $pendingAmounts['Jamaica National Bank Loan'],
                                        'pending_sagicor_bank_loan' => $pendingAmounts['Sagicor Bank Loan'],
                                        'pending_health_insurance' => $pendingAmounts['Health Insurance'],
                                        'pending_life_insurance' => $pendingAmounts['Life Insurance'],
                                        'pending_overpayment' => $pendingAmounts['Overpayment'],
                                        'pending_training' => $pendingAmounts['Training'],
                                        'is_publish' => 0,
                                    ]);

                                    if (EmployeePayroll::create($payrollData)) {
                                        echo "Employee payroll created successfully";
                                    } else {
                                        echo "Employee payroll not created";
                                    }
                                } else {
                                    echo "Employee ratemaster not created";
                                }
                            } else {
                                echo "Employee payroll already created";
                            }
                        } else {
                            echo "Employee joining days are 0.";
                        }
                    } else {
                        echo "Employee payroll dates not matched (Expected: {$previousEndDate->format('Y-m-d')}, Actual: {$today->format('Y-m-d')})";
                    }
                }
            }
        } else {
            echo "No employee found";
        }
    }

    protected function getProcessingDate(Carbon $today)
    {
        if ($today->month == 12) {
            $processingDate = Carbon::create($today->year, 12, 12)->startOfDay();
        } else {
            $processingDate = Carbon::create($today->year, $today->month, 25)->startOfDay();
        }

        while ($processingDate->isWeekend() || $this->isPublicHoliday($processingDate)) {
            $processingDate->subDay();
        }

        return $processingDate;
    }

    // protected function calculateLeaveDetails($normalDays, $employee, $previousStartDate, $endDate, $daySalary)
    // {
    //     $leavePaid = 0;
    //     $leaveNotPaid = 0;
    //     $grossSalary = $normalDays * $daySalary;

    //     $paidLeaveBalance = 0;
    //     $baseYearlyLimit = (int) setting('yearly_leaves') ?: 10;

    //     $year = Carbon::parse($previousStartDate)->year;
    //     $previousYear = $year - 1;

    //     $hasPreviousYearLeaves = EmployeeLeave::where('employee_id', $employee->id)
    //         ->where('status', 'Approved')
    //         ->whereYear('date', $previousYear)
    //         ->exists();

    //     $usedLeavesLastYear = 0;
    //     $carryForwardLeaves = 0;

    //     if ($hasPreviousYearLeaves) {
    //         $usedLeavesLastYear = EmployeeLeave::where('employee_id', $employee->id)
    //             ->where('status', 'Approved')
    //             ->whereYear('date', $previousYear)
    //             ->get()
    //             ->sum(function ($leave) {
    //                 $leaveDate = Carbon::parse($leave->date);
    //                 if ($leaveDate->isWeekend() || $this->isPublicHoliday($leaveDate)) {
    //                     return 0;
    //                 }
    //                 return ($leave->type === 'Half Day') ? 0.5 : 1;
    //             });

    //         $carryForwardLeaves = max(0, $baseYearlyLimit - $usedLeavesLastYear);
    //         $carryForwardLimit = 10;
    //         $carryForwardLeaves = min($carryForwardLeaves, $carryForwardLimit);
    //     }

    //     $paidLeaveBalanceLimit = $baseYearlyLimit + $carryForwardLeaves;

    //     $lastDayOfDecember = Carbon::createFromDate($year, 12, 13);
    //     $leavesQuery = EmployeeLeave::where('employee_id', $employee->id)
    //         ->where('status', 'Approved');

    //     $leavesCountInDecember = $leavesQuery->whereYear('date', $lastDayOfDecember->year)->get()
    //         ->sum(function ($leave) {
    //             $leaveDate = Carbon::parse($leave->date);
    //             if ($leaveDate->isWeekend() || $this->isPublicHoliday($leaveDate)) {
    //                 return 0;
    //             }
    //             return ($leave->type == 'Half Day') ? 0.5 : 1;
    //         });

    //     if ($lastDayOfDecember->between($previousStartDate, $endDate)) {
    //         $paidLeaveBalance = max(0, $paidLeaveBalanceLimit - $leavesCountInDecember);
    //     }

    //     $leavesCount = $leavesQuery->whereBetween('date', [$previousStartDate, $endDate])->get()
    //         ->sum(function ($leave) {
    //             $leaveDate = Carbon::parse($leave->date);
    //             if ($leaveDate->isWeekend() || $this->isPublicHoliday($leaveDate)) {
    //                 return 0;
    //             }
    //             return ($leave->type == 'Half Day') ? 0.5 : 1;
    //         });

    //     if ($leavesCount > 0) {
    //         $approvedLeaves = EmployeeLeave::where('employee_id', $employee->id)
    //             ->where('status', 'Approved')
    //             ->whereDate('date', '<', $previousStartDate)
    //             ->get()
    //             ->sum(function ($leave) {
    //                 $leaveDate = Carbon::parse($leave->date);
    //                 if ($leaveDate->isWeekend() || $this->isPublicHoliday($leaveDate)) {
    //                     return 0;
    //                 }
    //                 return ($leave->type == 'Half Day') ? 0.5 : 1;
    //             });

    //         $totalApprovedLeaves = $leavesCount + $approvedLeaves;

    //         if ($totalApprovedLeaves > $paidLeaveBalanceLimit) {
    //             $excessLeaves = max(0, $totalApprovedLeaves - $paidLeaveBalanceLimit);

    //             if ($excessLeaves > 0) {
    //                 if ($leavesCount > $excessLeaves) {
    //                     $leaveNotPaid = min($excessLeaves, 22);
    //                     $leavePaid = max(0, $leavesCount - $leaveNotPaid);
    //                 } else {
    //                     $leaveNotPaid = min($leavesCount, 22);
    //                     $leavePaid = 0;
    //                 }
    //             } else {
    //                 $leaveNotPaid = 0;
    //                 $leavePaid = min($leavesCount, 22);
    //             }

    //             $maxDeductibleAmount = $normalDays * $daySalary;
    //             $deductionAmount = min($excessLeaves * $daySalary, $maxDeductibleAmount);
    //             $grossSalary = max(0, $grossSalary - $deductionAmount);
    //         } else {
    //             $leavePaid = $leavesCount;
    //             $leaveNotPaid = 0;
    //         }
    //     }

    //     $pendingLeaveAmount = $paidLeaveBalance * $daySalary;
    //     $normalDaysSalary = $grossSalary;

    //     $leaveEncashments = LeaveEncashment::where('employee_id', $employee->id)
    //         ->whereDate('created_at', '<=', $endDate)
    //         ->get();

    //     $encashLeaveDays = $leaveEncashments->sum('encash_leaves');
    //     $encashLeaveAmount = $encashLeaveDays * $daySalary;

    //     $grossSalary += $encashLeaveAmount;

    //     return [
    //         $leavePaid,
    //         $leaveNotPaid,
    //         $paidLeaveBalance,
    //         $grossSalary,
    //         $pendingLeaveAmount,
    //         $normalDaysSalary
    //     ];
    // }

    protected function calculateLeaveDetails($normalDays, $employee, $previousStartDate, $endDate, $daySalary)
    {
        $leavePaid = 0;
        $leaveNotPaid = 0;
        $grossSalary = $normalDays * $daySalary;

        $paidLeaveBalance = 0;
        $normalDaysSalary = $grossSalary;

        $leaveTypes = [
            'Sick Leave' => (int) setting('yearly_leaves') ?: 10,
            'Vacation Leave' => (int) setting('vacation_leaves') ?: 10,
            'Maternity Leave' => (int) setting('maternity_leaves') ?: 44,
        ];

        $year = Carbon::parse($previousStartDate)->year;
        $previousYear = $year - 1;
        $carryForwardLimit = 10;

        $leaveTypeUsed = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereBetween('date', [$previousStartDate, $endDate])
            ->pluck('leave_type')
            ->unique()
            ->first();

        if (!$leaveTypeUsed || !array_key_exists($leaveTypeUsed, $leaveTypes)) {
            return [$leavePaid, $leaveNotPaid, 0, $grossSalary, 0, $normalDaysSalary];
        }

        $baseYearlyLimit = $leaveTypes[$leaveTypeUsed];

        // Used leaves last year (for carry forward calculation)
        $usedLastYear = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where('leave_type', $leaveTypeUsed)
            ->whereYear('date', $previousYear)
            ->get()
            ->sum(function ($leave) {
                $date = Carbon::parse($leave->date);
                return ($date->isWeekend() || $this->isPublicHoliday($date)) ? 0 : ($leave->type == 'Half Day' ? 0.5 : 1);
            });

        // Apply carry forward logic only from second year onward
        $carryForwardLeaves = 0;
        if ($usedLastYear > 0 || $previousYear < now()->year) {
            $carryForwardLeaves = min(max(0, $baseYearlyLimit - $usedLastYear), $carryForwardLimit);
        }

        $paidLeaveBalanceLimit = $baseYearlyLimit + $carryForwardLeaves;

        // Get leaves in current cycle (this year)
        $leavesQuery = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where('leave_type', $leaveTypeUsed);

        $leavesCount = $leavesQuery->whereBetween('date', [$previousStartDate, $endDate])
            ->get()
            ->sum(function ($leave) {
                $date = Carbon::parse($leave->date);
                return ($date->isWeekend() || $this->isPublicHoliday($date)) ? 0 : ($leave->type == 'Half Day' ? 0.5 : 1);
            });

        // December logic to set remaining balance
        $lastDayOfDecember = Carbon::createFromDate($year, 12, 31);
        $leavesInDecember = $leavesQuery->whereYear('date', $year)
            ->get()
            ->sum(function ($leave) {
                $date = Carbon::parse($leave->date);
                return ($date->isWeekend() || $this->isPublicHoliday($date)) ? 0 : ($leave->type == 'Half Day' ? 0.5 : 1);
            });

        if ($lastDayOfDecember->between($previousStartDate, $endDate)) {
            $paidLeaveBalance = max(0, $paidLeaveBalanceLimit - $leavesInDecember);
        }

        // Prior approved leaves
        $approvedLeavesBefore = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where('leave_type', $leaveTypeUsed)
            ->whereDate('date', '<', $previousStartDate)
            ->get()
            ->sum(function ($leave) {
                $date = Carbon::parse($leave->date);
                return ($date->isWeekend() || $this->isPublicHoliday($date)) ? 0 : ($leave->type == 'Half Day' ? 0.5 : 1);
            });

        $totalApprovedLeaves = $leavesCount + $approvedLeavesBefore;

        if ($totalApprovedLeaves > $paidLeaveBalanceLimit) {
            $excess = max(0, $totalApprovedLeaves - $paidLeaveBalanceLimit);
            if ($leavesCount > $excess) {
                $leaveNotPaid = min($excess, 22);
                $leavePaid = max(0, $leavesCount - $leaveNotPaid);
            } else {
                $leaveNotPaid = min($leavesCount, 22);
                $leavePaid = 0;
            }

            $deductionAmount = min($leaveNotPaid * $daySalary, $normalDays * $daySalary);
            $grossSalary = max(0, $grossSalary - $deductionAmount);
        } else {
            $leavePaid = $leavesCount;
            $leaveNotPaid = 0;
        }

        $pendingLeaveAmount = $paidLeaveBalance * $daySalary;

        // Encashments
        $leaveEncashments = LeaveEncashment::where('employee_id', $employee->id)
            ->whereDate('created_at', '<=', $endDate)
            ->get();

        $encashLeaveDays = $leaveEncashments->sum('encash_leaves');
        $encashLeaveAmount = $encashLeaveDays * $daySalary;
        $grossSalary += $encashLeaveAmount;

        return [$leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary, $pendingLeaveAmount, $normalDaysSalary];
    }

    public function calculateEmployeePayrollStatutory($employee, $previousStartDate, $endDate, $grossSalary, $daySalary)
    {
        $approvedPensionScheme = 0;
        $userData = User::with('guardAdditionalInformation')->where('id', $employee->id)->first();
        $dateOfBirth = $userData->guardAdditionalInformation->date_of_birth;
        $birthDate = Carbon::parse($dateOfBirth);
        $age = $birthDate->age;

        $currentYear = Carbon::now()->year;
        $fullYearNis = EmployeePayroll::where('employee_id', $employee->id)->whereYear('created_at', $currentYear)->get();

        $payeIncome = 0;
        $employer_contribution = 0;
        $employerContributionNht = 0;
        $nhtDeduction = 0;
        $eduction_tax = 0;
        $hearttax = 0;
        $nis = 0;
        $employerContributionNis = 0;

        if ($userData->is_statutory == 0) {
            $totalNisForCurrentYear = $fullYearNis->sum('nis');

            if ($age >= 70) {
                $nis = 0;
                $employerContributionNis = 0;
            } else {
                if ($totalNisForCurrentYear < 150000) {
                    $nisDeduction = $grossSalary * 0.03;
                    $employerContributionNis = $grossSalary * 0.03;
                    $remainingNisToReachLimit = 150000 - $totalNisForCurrentYear;
                    if ($nisDeduction > $remainingNisToReachLimit) {
                        $nis = $remainingNisToReachLimit;
                    } else {
                        $nis = $nisDeduction;
                    }
                } else {
                    $nis = 0;
                    $employerContributionNis = 0;
                }
            }

            // if ($age >= 70) {
            //     $nis = 0;
            //     $employerContributionNis = 0;
            // } else {
            //     $nisDeduction = $grossSalary * 0.03;
            //     $nisThreshold  = 150000 / 12;
            //     if ($nisDeduction > $nisThreshold) {
            //         $nis = $nisThreshold;
            //         $employerContributionNis = $nisThreshold;
            //     } else {
            //         $nis = $daySalary * 0.03;
            //         $employerContributionNis = $daySalary * 0.03;
            //     }
            // }
            /*if ($statutoryIncome < 141674) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > 141674 && $statutoryIncome <= 500000.00) {
                $payeData = $statutoryIncome - 141674;
                $payeIncome = $payeData * 0.25;
            } elseif ($statutoryIncome > 500000.00) {
                $payeData = ($statutoryIncome - 500000.00) * 0.30;
                $payeeThreshold = (500000.00 - 141674.00) * 0.25;
                $payeIncome = $payeData + $payeeThreshold;
            }*/

            /* $statutoryIncome  = $grossSalary -  $nis - $approvedPensionScheme;
            if ($age >= 65) {
                $threshold = 162507.33; // (1,700,088 + 250,000) / 12
            } else {
                $threshold = 141674.00;
            }
            if ($statutoryIncome < $threshold) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > $threshold && $statutoryIncome <= 500000.00) {
                $payeData = $statutoryIncome - $threshold;
                $payeIncome = $payeData * 0.25;
            } elseif ($statutoryIncome > 500000.00) {
                $payeData = ($statutoryIncome - 500000.00) * 0.30;
                $payeeThreshold = (500000.00 - $threshold) * 0.25;
                $payeIncome = $payeData + $payeeThreshold;
            }*/

            $statutoryIncome = $grossSalary - $nis - $approvedPensionScheme;

            // Get applicable threshold based on current date
            $currentDate = Carbon::now();
            $latestThreshold = EmployeeTaxThreshold::whereDate('effective_date', '<=', $currentDate)
                ->orderBy('effective_date', 'desc')
                ->first();

            // Optional fallback
            if (!$latestThreshold) {
                $latestThreshold = EmployeeTaxThreshold::orderBy('effective_date', 'asc')->first();
            }
            // Fallback again if still null (just to be safe)
            if (!$latestThreshold) {
                throw new \Exception('No tax threshold data found.');
            }
            $seniorCitizenBonus = 250000;
            // Calculate threshold based on age
            if ($age >= 65) {
                $threshold = ($latestThreshold->annual + $seniorCitizenBonus) / 12;
            } else {
                $threshold = $latestThreshold->monthly;
            }

            // Define next slab monthly (e.g., 6M annual → 500k monthly)
            // Ideally, fetch this from DB or config if dynamic
            $nextSlabMonthly = 500000.00;

            // PAYE logic
            if ($statutoryIncome < $threshold) {
                $payeIncome = 0;
            } elseif ($statutoryIncome <= $nextSlabMonthly) {
                $payeIncome = ($statutoryIncome - $threshold) * 0.25;
            } else {
                $payeData = ($statutoryIncome - $nextSlabMonthly) * 0.30;
                $payeeThreshold = ($nextSlabMonthly - $threshold) * 0.25;
                $payeIncome = $payeData + $payeeThreshold;
            }

            if ($age >= 65) {
                $eduction_tax = 0;
                $employer_contribution = 0;
            } else {
                $eduction_tax = $statutoryIncome * 0.0225;
                $employer_contribution = $statutoryIncome * 0.035;
            }

            if ($age >= 65) {
                $nhtDeduction = 0;
                $employerContributionNht = 0;
            } else {
                $nhtDeduction = $grossSalary * 0.02;
                $employerContributionNht =  $grossSalary * 0.03;
            }

            $hearttax = $grossSalary * 0.03;
        } else {
            $statutoryIncome  = $grossSalary -  $nis - $approvedPensionScheme;
        }

        return [
            'statutory_income' => $statutoryIncome,
            'nis' => $nis,
            'employer_contribution_nis_tax' => $employerContributionNis,
            'paye' => $payeIncome,
            'education_tax' => $eduction_tax,
            'employer_eduction_tax' => $employer_contribution,
            'nht' => $nhtDeduction,
            'employer_contribution_nht_tax' => $employerContributionNht,
            'heart' => $hearttax,
        ];
    }

    private function calculateNonStatutoryDeductions($employee, $previousStartDate, $endDate)
    {
        $deductionTypes = [
            'Staff Loan' => 'pending_staff_loan',
            'Medical Ins' => 'pending_medical_insurance',
            'Salary Advance' => 'pending_salary_advance',
            'PSRA' => 'pending_psra',
            'Bank Loan' => 'pending_bank_loan',
            'Approved Pension' => 'pending_approved_pension',
            'Garnishment' => 'pending_garnishment',
            'Missing Goods' => 'pending_missing_goods',
            'Damaged Goods' => 'pending_damaged_goods',
            'NCB Loan' => 'pending_ncb_loan',
            'C&WJ Credit Union Loan' => 'pending_cwj_credit_union_loan',
            'Edu Com Co-op Loan' => 'pending_edu_com_coop_loan',
            'National Housing Trust Mortgage Loan' => 'pending_nht_mortgage_loan',
            'Jamaica National Bank Loan' => 'pending_jamaica_national_bank_loan',
            'Sagicor Bank Loan' => 'pending_sagicor_bank_loan',
            'Health Insurance' => 'pending_health_insurance',
            'Life Insurance' => 'pending_life_insurance',
            'Overpayment' => 'pending_overpayment',
            'Training' => 'pending_training'
        ];

        $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);

        foreach ($deductionTypes as $deductionType => $pendingField) {
            $deductionRecords = EmployeeDeduction::where('employee_id', $employee->id)
                ->where('type', $deductionType)
                ->whereDate('start_date', '<=', $endDate)
                ->get();
            echo "End Date : " . $endDate->format('Y-m-d') . " Deduction Type : " . $deductionType . "\n";
            // dd($deductionRecords);

            foreach ($deductionRecords as $deduction) {
                if (!is_null($deduction->end_date) && $deduction->start_date <= $endDate && $deduction->end_date >= $previousStartDate) {
                    $deductionAmount = min($deduction->one_installment, $deduction->pending_balance);
                    $newBalance = $deduction->pending_balance - $deductionAmount;

                    $totalDeductions[$deductionType] += $deductionAmount;

                    // ✅ Only update if end_date is present
                    $deduction->update(['pending_balance' => $newBalance]);

                    EmployeeDeductionDetail::create([
                        'employee_id' => $employee->id,
                        'deduction_id' => $deduction->id,
                        'deduction_date' => Carbon::now(),
                        'amount_deducted' => $deductionAmount,
                        'balance' => $newBalance
                    ]);
                } elseif (is_null($deduction->end_date) && is_null($deduction->no_of_payroll)) {
                    if ($deduction->pending_balance > 0) {
                        $deductionAmount = min($deduction->one_installment, $deduction->pending_balance);
                        $newBalance = $deduction->pending_balance - $deductionAmount;

                        $totalDeductions[$deductionType] += $deductionAmount;
                        // ✅ Do not update pending_balance if end_date is not present
                        // $deduction->update(['pending_balance' => $newBalance]);
                        EmployeeDeductionDetail::create([
                            'employee_id' => $employee->id,
                            'deduction_id' => $deduction->id,
                            'deduction_date' => Carbon::now(),
                            'amount_deducted' => $deductionAmount,
                            'balance' => 0 //$deduction->pending_balance // Show current balance without change
                        ]);
                    }
                }
            }

            $deductionIds = EmployeeDeduction::where('employee_id', $employee->id)
                ->where('type', $deductionType)
                ->pluck('id');

            $pendingAmounts[$deductionType] = EmployeeDeductionDetail::whereIn('deduction_id', $deductionIds)
                ->select('deduction_id', DB::raw('MAX(id) as latest_id'))
                ->groupBy('deduction_id')
                ->pluck('latest_id')
                ->pipe(function ($latestIds) {
                    return EmployeeDeductionDetail::whereIn('id', $latestIds)->sum('balance');
                });
        }

        // dd($totalDeductions, $pendingAmounts);
        return [$totalDeductions, $pendingAmounts];
    }

    protected function isPublicHoliday($date)
    {
        return PublicHoliday::whereDate('date', $date)->exists();
    }

    // private function calculateNonStatutoryDeductions($employee, $previousStartDate, $endDate)
    // {
    //     // Deduction types
    //     $deductionTypes = [
    //         'Staff Loan' => 'pending_staff_loan',
    //         'Medical Ins' => 'pending_medical_insurance',
    //         'Salary Advance' => 'pending_salary_advance',
    //         'PSRA' => 'pending_psra',
    //         'Bank Loan' => 'pending_bank_loan',
    //         'Approved Pension' => 'pending_approved_pension',
    //         'Garnishment' => 'pending_garnishment',
    //         'Missing Goods' => 'pending_missing_goods',
    //         'Damaged Goods' => 'pending_damaged_goods',
    //     ];

    //     // Initialize deduction arrays
    //     $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);
    //     $pendingAmounts = array_fill_keys(array_keys($deductionTypes), 0);

    //     // Check if employee is statutory or non-statutory
    //     // if ($employee->is_statutory == 1) {
    //         // Statutory employee logic
    //         foreach ($deductionTypes as $deductionType => $pendingField) {
    //             $deductionRecords = EmployeeDeduction::where('employee_id', $employee->id)
    //                 ->where('type', $deductionType)
    //                 ->whereDate('start_date', '<=', $endDate)
    //                 ->whereDate('end_date', '>=', $previousStartDate)
    //                 ->get();

    //             foreach ($deductionRecords as $deduction) {
    //                 if ($deduction->start_date <= $endDate && $deduction->end_date >= $previousStartDate) {
    //                     $totalDeductions[$deductionType] = $deduction->one_installment;
    //                     $pendingBalance = $deduction->pending_balance - $deduction->one_installment;
    //                     $pendingAmounts[$deductionType] = $deduction->pending_balance - $deduction->one_installment;
    //                     $deduction->update(['pending_balance' => $pendingBalance]);

    //                     EmployeeDeductionDetail::create([
    //                         'employee_id' => $employee->id,
    //                         'deduction_id' => $deduction->id,
    //                         'deduction_date' => Carbon::now(),
    //                         'amount_deducted' => $deduction->one_installment,
    //                         'balance' => $pendingBalance
    //                     ]);
    //                 }
    //             }
    //         }
    //     // } else {
    //     //     $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);
    //     //     $pendingAmounts = array_fill_keys(array_keys($deductionTypes), 0);
    //     // }

    //     return [$totalDeductions, $pendingAmounts];
    // }
}
