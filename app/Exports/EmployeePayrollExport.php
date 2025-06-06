<?php

namespace App\Exports;

use App\Models\EmployeePayroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeePayrollExport implements FromCollection, WithHeadings
{
    protected $year;
    protected $month;

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        return EmployeePayroll::with('user')
            ->whereYear('start_date', $this->year)
            ->whereMonth('start_date', $this->month)
            ->get()
            ->map(function ($payroll) {
                return [
                    $payroll->user->first_name . ' ' . $payroll->user->surname,
                    $payroll->start_date,
                    $payroll->end_date,
                    $payroll->normal_days,
                    $payroll->leave_paid,
                    $payroll->leave_not_paid,
                    $payroll->pending_leave_balance,
                    $payroll->day_salary,
                    $payroll->normal_salary,
                    $payroll->pending_leave_amount,
                    $payroll->gross_salary,
                    $payroll->paye,
                    $payroll->education_tax,
                    $payroll->employer_eduction_tax,
                    $payroll->nis,
                    $payroll->employer_contribution_nis_tax,
                    $payroll->statutory_income,
                    $payroll->nht,
                    $payroll->employer_contribution_nht_tax,
                    $payroll->heart,
                    $payroll->staff_loan,
                    $payroll->pending_staff_loan,
                    $payroll->medical_insurance,
                    $payroll->pending_medical_insurance,
                    $payroll->salary_advance,
                    $payroll->pending_salary_advance,
                    $payroll->approved_pension_scheme,
                    $payroll->pending_approved_pension,
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
                    $payroll->threshold,
                    $payroll->is_publish ? 'Yes' : 'No',
                    $payroll->created_at,
                    $payroll->updated_at,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Start Date',
            'End Date',
            'Normal Days',
            'Paid Leaves',
            'Unpaid Leaves',
            'Pending Leave Balance',
            'Day Salary',
            'Normal Salary',
            'Pending Leave Amount',
            'Gross Salary',
            'PAYE',
            'Education Tax',
            'Employer Education Tax',
            'NIS',
            'Employer NIS Contribution',
            'Statutory Income',
            'NHT',
            'Employer NHT Contribution',
            'HEART',
            'Staff Loan',
            'Pending Staff Loan',
            'Medical Insurance',
            'Pending Medical Insurance',
            'Salary Advance',
            'Pending Salary Advance',
            'Approved Pension Scheme',
            'Pending Approved Pension',
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
            'Threshold',
            'Is Published',
            'Created At',
            'Updated At',
        ];
    }
}
