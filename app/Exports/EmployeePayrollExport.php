<?php

namespace App\Exports;

use App\Models\EmployeePayroll;
use App\Models\EmployeeOvertime;
use App\Models\LeaveEncashment;
use App\Models\EmployeeLeave;
use App\Models\EmployeeRateMaster;
use App\Models\TwentyTwoDayInterval;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class EmployeePayrollExport implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected $year;
    protected $month;
    protected $paidLeaveBalanceLimit;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
    }

    public function collection()
    {
        return EmployeePayroll::with(['user', 'user.guardAdditionalInformation'])
            ->whereYear('start_date', $this->year)
            ->whereMonth('start_date', $this->month)
            ->get()
            ->map(function ($payroll) {
                // Add additional data similar to your PDF download method
                $payroll = $this->enrichPayrollData($payroll);
                return $payroll;
            });
    }

    protected function enrichPayrollData($payroll)
    {
        $employeeRate = EmployeeRateMaster::where('employee_id', $payroll->employee_id)->first();
        $payroll->daySalary = $employeeRate?->daily_income ?? 0;
        $payroll->employeeAllowance = $employeeRate?->employee_allowance ?? 0;

        $overtimeTotal = EmployeeOvertime::where('employee_id', $payroll->employee_id)
            ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
            ->sum('overtime_income');
        $payroll->overtimeHours = EmployeeOvertime::where('employee_id', $payroll->employee_id)
            ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
            ->sum('hours');
        $payroll->overtime_income_total = $overtimeTotal;

        $leaveEncashments = LeaveEncashment::where('employee_id', $payroll->employee_id)
            ->whereDate('created_at', '<=', $payroll->end_date)
            ->get();
        $payroll->encashLeaveDays = $leaveEncashments->sum('encash_leaves');
        $payroll->encashLeaveAmount = $payroll->encashLeaveDays * $payroll->daySalary;

        $approvedLeaves = EmployeeLeave::where('employee_id', $payroll->employee_id)
            ->where('status', 'Approved')
            ->whereDate('date', '<=', $payroll->end_date)
            ->whereYear('date', $this->year)
            ->get()
            ->sum(function ($leave) {
                return ($leave->type == 'Half Day') ? 0.5 : 1;
            });
        $payroll->pendingLeaveBalance = max(0, $this->paidLeaveBalanceLimit - $approvedLeaves);

        $fullYearPayroll = EmployeePayroll::where('employee_id', $payroll->employee_id)
            ->whereDate('end_date', '<=', $payroll->end_date)
            ->whereYear('created_at', $this->year)
            ->orderBy('created_at', 'desc')
            ->get();

        $payroll->gross_total = $fullYearPayroll->sum('gross_salary');
        $payroll->nis_total = $fullYearPayroll->sum('nis');
        $payroll->paye_tax_total = $fullYearPayroll->sum('paye');
        $payroll->education_tax_total = $fullYearPayroll->sum('education_tax');
        $payroll->nht_total = $fullYearPayroll->sum('nht');

        return $payroll;
    }

    public function map($payroll): array
    {
        // Calculate derived values
        $grossSalaryTaxable = $payroll->normal_salary +
            $payroll->overtime_income_total +
            $payroll->encashLeaveAmount +
            ($payroll->leave_paid * $payroll->daySalary);

        $statutoryIncome = $grossSalaryTaxable - $payroll->nis;
        $taxableIncome = $statutoryIncome;

        $totalDeductions = $payroll->paye +
            $payroll->education_tax +
            $payroll->nis +
            $payroll->nht +
            $payroll->staff_loan +
            $payroll->medical_insurance +
            $payroll->salary_advance +
            $payroll->approved_pension_scheme +
            $payroll->psra +
            $payroll->bank_loan +
            $payroll->missing_goods +
            $payroll->damaged_goods +
            $payroll->garnishment +
            $payroll->ncb_loan;

        $netSalary = ($payroll->gross_salary + $payroll->employeeAllowance) - $totalDeductions;

        return [
            // Employee Information
            $payroll->user->user_code ?? 'N/A',
            $payroll->user->first_name . ' ' . $payroll->user->surname,
            Carbon::parse($payroll->start_date)->format('d-M-Y') . ' to ' . Carbon::parse($payroll->end_date)->format('d-M-Y'),

            // Earnings
            $payroll->daySalary,
            $payroll->normal_days - $payroll->leave_not_paid,
            $payroll->normal_salary,
            $payroll->leave_paid,
            $payroll->employeeAllowance,
            $payroll->pendingLeaveBalance > 0 ? $payroll->pendingLeaveBalance : $payroll->leave_not_paid,
            $payroll->pendingLeaveBalance > 0 ? $payroll->pending_leave_amount : 0,
            $payroll->overtimeHours,
            $payroll->overtime_income_total,
            $payroll->encashLeaveDays,
            $payroll->encashLeaveAmount,

            // Calculated Earnings
            $grossSalaryTaxable,
            $statutoryIncome,
            $taxableIncome,

            // Deductions
            $payroll->paye,
            $payroll->education_tax,
            $payroll->nis,
            $payroll->nht,
            $payroll->staff_loan,
            $payroll->pending_staff_loan,
            $payroll->medical_insurance,
            $payroll->pending_medical_insurance,
            $payroll->salary_advance,
            $payroll->pending_salary_advance,
            $payroll->approved_pension_scheme,
            // $payroll->pending_approved_pension,
            $payroll->psra,
            $payroll->pending_psra,
            $payroll->bank_loan,
            $payroll->pending_bank_loan,
            $payroll->garnishment,
            $payroll->pending_garnishment,
            $payroll->missing_goods,
            $payroll->pending_missing_goods,
            $payroll->damaged_goods,
            $payroll->pending_damaged_goods,
            $payroll->ncb_loan,
            $payroll->pending_ncb_loan,
            $payroll->cwj_credit_union_loan,
            $payroll->pending_cwj_credit_union_loan,
            $payroll->edu_com_coop_loan,
            $payroll->pending_edu_com_coop_loan,
            $payroll->nht_mortgage_loan,
            $payroll->pending_nht_mortgage_loan,
            $payroll->jn_bank_loan,
            $payroll->pending_jn_bank_loan,
            $payroll->sagicor_bank_loan,
            $payroll->pending_sagicor_bank_loan,
            $payroll->health_insurance,
            $payroll->life_insurance,
            $payroll->overpayment,
            $payroll->training,

            // Employer Contributions
            $payroll->employer_eduction_tax,
            $payroll->employer_contribution_nis_tax,
            $payroll->employer_contribution_nht_tax,
            $payroll->heart,

            // Totals
            $payroll->gross_salary + $payroll->employeeAllowance,
            $totalDeductions,
            $netSalary,

            // Year-to-date
            $payroll->gross_total + $payroll->employeeAllowance ?? '0',
            $payroll->nis_total,
            $payroll->paye_tax_total,
            $payroll->education_tax_total,
            $payroll->nht_total,
        ];
    }

    public function headings(): array
    {
        return [
            // Employee Information
            'Employee No.',
            'Employee Name',
            'Payroll Period',

            // Earnings Headers
            'Daily Salary',
            'Worked Days',
            'Normal Salary',
            'Paid Leave Days',
            'Employee Allowance',
            'Leave Not Paid/Pending Balance',
            'Pending Leave Amount',
            'Overtime Hours',
            'Overtime Amount',
            'Encash Leave Days',
            'Encash Leave Amount',

            // Calculated Earnings
            'Gross Salary (Taxable)',
            'Statutory Income',
            'Taxable Income',

            // Deductions Headers
            'PAYE',
            'Education Tax',
            'NIS',
            'NHT',
            'Staff Loan',
            'Pending Staff Loan',
            'Medical Insurance',
            'Pending Medical Insurance',
            'Salary Advance',
            'Pending Salary Advance',
            'Approved Pension Scheme',
            // 'Pending Approved Pension',
            'PSRA',
            'Pending PSRA',
            'Bank Loan',
            'Pending Bank Loan',
            'Garnishment',
            'Pending Garnishment',
            'Missing Goods',
            'Pending Missing Goods',
            'Damaged Goods',
            'Pending Damaged Goods',
            'NCB Loan',
            'Pending NCB Loan',
            'C&WJ Credit Union Loan',
            'Pending C&WJ Credit Union Loan',
            'Edu Com Co-op Loan',
            'Pending Edu Com Co-op Loan',
            'National Housing Trust Mortgage Loan',
            'Pending National Housing Trust Mortgage Loan',
            'Jamaica National Bank Loan',
            'Pending Jamaica National Bank Loan',
            'Sagicor Bank Loan',
            'Pending Sagicor Bank Loan',
            'Health Insurance',
            'Life Insurance',
            'Overpayment',
            'Training',

            // Employer Contributions
            'Employer Education Tax',
            'Employer NIS Contribution',
            'Employer NHT Contribution',
            'HEART',

            // Totals
            'Total Earnings',
            'Total Deductions',
            'Net Salary',

            // Year-to-date
            'YTD Gross Earnings',
            'YTD NIS',
            'YTD PAYE',
            'YTD Education Tax',
            'YTD NHT',
        ];
    }

    public function title(): string
    {
        return 'Payroll Details ' . $this->month . '-' . $this->year;
    }
}
