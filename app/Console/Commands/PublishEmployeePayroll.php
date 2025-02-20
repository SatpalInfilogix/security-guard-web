<?php

namespace App\Console\Commands;

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
                $today = Carbon::parse('11-03-2025')->startOfDay();
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
                                $yearlySalary = $employeeRateMaster->gross_salary;
                                $daySalary = ($yearlySalary / 12) / 22;

                                //===== Employee Leaves Calculation =====
                                list($leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary) = $this->calculateLeaveDetails($normalDays, $employee, $previousStartDate, $previousEndDate, $daySalary);
                                //===== End Leaves Calculation =====

                                //===== Employee Payroll Calculation =====
                                $payrollData = $this->calculateEmployeePayroll($employee, $previousStartDate, $previousEndDate, $grossSalary);
                                // ========End Payroll Calculation ========

                                $payrollData = array_merge($payrollData, [
                                    'employee_id' => $employee->id,
                                    'start_date' => $previousStartDate,
                                    'end_date' => $previousEndDate,
                                    'normal_days' => $normalDays,
                                    'leave_paid' => $leavePaid,
                                    'leave_not_paid' => $leaveNotPaid,
                                    'pending_leave_balance' => $paidLeaveBalance,
                                    'day_salary' => $daySalary,
                                    'gross_salary' => $grossSalary,
                                    'is_publish' => 0,
                                ]);
                        
                                if (EmployeePayroll::create($payrollData)) {
                                    echo "Employee payroll created successfully";
                                } else {
                                    echo "Employee payroll not created";
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
        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?? 10;

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

        $grossSalary += $paidLeaveBalance * $daySalary;

        return [$leavePaid, $leaveNotPaid, $paidLeaveBalance, $grossSalary];
    }

    public function calculateEmployeePayroll($employee, $previousStartDate, $previousEndDate, $grossSalary)
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

            $statutoryIncome  = $grossSalary -  $nis - $approvedPensionScheme;

            if ($statutoryIncome < 141674) {
                $payeIncome = 0;
            } elseif ($statutoryIncome > 141674 && $statutoryIncome <= 500000.00) {
                $payeData = $statutoryIncome - 141674;
                $payeIncome = $payeData * 0.25;
            } elseif($statutoryIncome > 500000.00) {
                $payeData = $statutoryIncome - 500000.00;
                $payeIncome = $payeData * 0.30;
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

}
