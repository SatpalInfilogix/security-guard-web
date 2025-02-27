<?php

namespace App\Console\Commands;

use App\Models\EmployeeDeduction;
use App\Models\EmployeeDeductionDetail;
use App\Models\EmployeeLeave;
use App\Models\EmployeeRateMaster;
use App\Models\User;
use App\Models\EmployeePayroll;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TwentyTwoDayInterval;

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
    public function handle()
    {
        $userRole = Role::where('name', 'Employee')->first();
        $employees = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->with('guardAdditionalInformation')->latest()->get();

        if($employees) {
            foreach ($employees as $employee) {
                // $today = Carbon::now()->startOfDay();
                $today = Carbon::parse('17-02-2025')->startOfDay(); //--Manual Check
                $twentyTwoDay = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
                if ($twentyTwoDay) {
                    $endDate = Carbon::parse($twentyTwoDay->end_date)->startOfDay();
                    $startDate = Carbon::parse($twentyTwoDay->start_date)->startOfDay();
                    
                    if ($startDate == $today) {
                        $previousEndDate = Carbon::parse($twentyTwoDay->start_date)->subDay();
                        $previousStartDate = $previousEndDate->copy()->subDays(21);
                        $joiningDate = Carbon::parse($employee->guardAdditionalInformation->date_of_joining);
                       
                        $startDateForEmployee = $joiningDate > $previousStartDate ? $joiningDate : $previousStartDate;
                    
                        if ($startDateForEmployee->greaterThan($previousEndDate)) {
                            $normalDays = 0;
                        } else {
                            $normalDays = $startDateForEmployee->diffInDays($previousEndDate) + 1;
                        }

                        if($normalDays > 0)
                        {
                            $payroll = EmployeePayroll::where('employee_id', $employee->id)->whereDate('start_date', $previousStartDate)->whereDate('end_date', $previousEndDate)->first();
                            if(!$payroll) {
                                $employeeRateMaster = EmployeeRateMaster::where('employee_id', $employee->id)->first();
                                if($employeeRateMaster) {
                                    $yearlySalary = $employeeRateMaster->gross_salary;
                                    $daySalary = ($yearlySalary / 12) / 22;

                                    //===== Employee Leaves Calculation =====
                                    list($leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary, $pendingLeaveAmount, $normalDaysSalary) = $this->calculateLeaveDetails($normalDays, $employee, $previousStartDate, $previousEndDate, $daySalary);
                                    //===== End Leaves Calculation =====

                                    //===== Employee Payroll Statutory Calculation =====
                                    $payrollStatutoryData = $this->calculateEmployeePayrollStatutory($employee, $previousStartDate, $previousEndDate, $grossSalary, $daySalary);
                                    // ========End Payroll Statutory Calculation ========
                                    
                                    //===== Employee Payroll Non Statutory Calculation =====
                                    list($totalDeductions, $pendingAmounts) = $this->calculateNonStatutoryDeductions($employee, $previousStartDate, $previousEndDate);
                                    //===== End Employee Payroll Non Statutory Calculation =====


                                    $payrollData = array_merge($payrollStatutoryData, [
                                        'employee_id' => $employee->id,
                                        'start_date' => $previousStartDate,
                                        'end_date' => $previousEndDate,
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
    }

    protected function calculateLeaveDetails($normalDays, $employee, $previousStartDate, $previousEndDate, $daySalary)
    {
        $leavePaid = 0;
        $leaveNotPaid = 0;
        $grossSalary = $normalDays * $daySalary;

        $paidLeaveBalance = 0;
        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;

        $year = Carbon::parse($previousStartDate)->year;
        $lastDayOfDecember = Carbon::createFromDate($year, 12, 31);
        $leavesQuery = EmployeeLeave::where('employee_id', $employee->id)->where('status', 'Approved');
        $leavesCountInDecember = $leavesQuery->whereYear('date', $lastDayOfDecember->year)->count();
        if ($lastDayOfDecember->between($previousStartDate, $previousEndDate)) {
            $paidLeaveBalance = max(0, $paidLeaveBalanceLimit - $leavesCountInDecember);
        }

        $leavesCount = $leavesQuery->whereBetween('date', [$previousStartDate, $previousEndDate])->count();
        if ($leavesCount > 0) {
            $approvedLeaves = EmployeeLeave::where('employee_id', $employee->id)->where('status', 'Approved')->whereDate('date', '<', $previousStartDate)->count();
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

                $grossSalary = $grossSalary - ($excessLeaves * $daySalary);
            } else {
                $leavePaid = $leavesCount;
                $leaveNotPaid = 0;
            }
        }

        $pendingLeaveAmount = $paidLeaveBalance * $daySalary;
        $normalDaysSalary = $grossSalary;

        $grossSalary += $paidLeaveBalance * $daySalary;

        return [$leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary, $pendingLeaveAmount, $normalDaysSalary];
    }

    public function calculateEmployeePayrollStatutory($employee, $previousStartDate, $previousEndDate, $grossSalary, $daySalary)
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

        if($userData->is_statutory == 0) {
            $totalNisForCurrentYear = $fullYearNis->sum('nis');

            // if ($age >= 70) {
            //     $nis = 0;
            //     $employerContributionNis = 0;
            // } else {
            //     if ($totalNisForCurrentYear < 150000) {
            //         $nisDeduction = $grossSalary * 0.03;
            //         $employerContributionNis = $grossSalary * 0.03;
            //         $remainingNisToReachLimit = 150000 - $totalNisForCurrentYear;
            //         if ($nisDeduction > $remainingNisToReachLimit) {
            //             $nis = $remainingNisToReachLimit;
            //         } else {
            //             $nis = $nisDeduction;
            //         }
            //     } else {
            //         $nis = 0;
            //         $employerContributionNis = 0;
            //     }
            // }

            if($age >= 70) {
                $nis = 0;
                $employerContributionNis = 0;
            } else {
                $nisDeduction = $grossSalary * 0.03;
                $nisThreshold  = 150000 / 12;
                if($nisDeduction > $nisThreshold) {
                    $nis = $nisThreshold;
                    $employerContributionNis = $nisThreshold;
                } else {
                    $nis = $daySalary * 0.03;
                    $employerContributionNis = $daySalary * 0.03;
                }
            }

            $statutoryIncome  = $grossSalary -  $nis - $approvedPensionScheme;

            if ($statutoryIncome < 141674) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > 141674 && $statutoryIncome <= 500000.00) {
                $payeData = $statutoryIncome - 141674;
                $payeIncome = $payeData * 0.25;
            } elseif($statutoryIncome > 500000.00) {
                $payeData = ($statutoryIncome - 500000.00) * 0.30;
                $payeeThreshold = (500000.00 - 141674.00) * 0.25;
                $payeIncome = $payeData + $payeeThreshold;
            }

            $eduction_tax = $statutoryIncome * 0.0225;
            $employer_contribution = $grossSalary * 0.035;

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

    private function calculateNonStatutoryDeductions($employee, $previousStartDate, $previousEndDate)
    {
        // Deduction types
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
        ];

        // Initialize deduction arrays
        $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);
        $pendingAmounts = array_fill_keys(array_keys($deductionTypes), 0);

        // Check if employee is statutory or non-statutory
        if ($employee->is_statutory == 1) {
            // Statutory employee logic
            foreach ($deductionTypes as $deductionType => $pendingField) {
                $deductionRecords = EmployeeDeduction::where('employee_id', $employee->id)
                    ->where('type', $deductionType)
                    ->whereDate('start_date', '<=', $previousEndDate)
                    ->whereDate('end_date', '>=', $previousStartDate)
                    ->get();

                foreach ($deductionRecords as $deduction) {
                    if ($deduction->start_date <= $previousEndDate && $deduction->end_date >= $previousStartDate) {
                        $totalDeductions[$deductionType] = $deduction->one_installment;
                        $pendingBalance = $deduction->pending_balance - $deduction->one_installment;
                        $pendingAmounts[$deductionType] = $deduction->pending_balance - $deduction->one_installment;
                        $deduction->update(['pending_balance' => $pendingBalance]);

                        EmployeeDeductionDetail::create([
                            'employee_id' => $employee->id,
                            'deduction_id' => $deduction->id,
                            'deduction_date' => Carbon::now(),
                            'amount_deducted' => $deduction->one_installment,
                            'balance' => $pendingBalance
                        ]);
                    }
                }
            }
        } else {
            // Non-statutory employee logic (No deductions)
            $totalDeductions = array_fill_keys(array_keys($deductionTypes), 0);
            $pendingAmounts = array_fill_keys(array_keys($deductionTypes), 0);
        }

        // Return total deductions and pending amounts
        return [$totalDeductions, $pendingAmounts];
    }
}
