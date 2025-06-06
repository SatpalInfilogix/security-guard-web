<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuardPayrollExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($dateRange)
    {
        if (strpos($dateRange, 'to') !== false) {
            [$start, $end] = explode(' to ', $dateRange);
            $this->startDate = \Carbon\Carbon::parse(trim($start))->startOfDay();
            $this->endDate = \Carbon\Carbon::parse(trim($end))->endOfDay();
        } else {
            $this->startDate = \Carbon\Carbon::parse($dateRange)->startOfDay();
            $this->endDate = \Carbon\Carbon::parse($dateRange)->endOfDay();
        }
    }

    public function collection()
    {
        return Payroll::with('user')
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->startDate, $this->endDate])
                    ->orWhereBetween('end_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->startDate)
                            ->where('end_date', '>=', $this->endDate);
                    });
            })
            ->get()
            ->map(function ($payroll) {
                return [
                    'Guard Name'              => optional($payroll->user)->first_name . ' ' . optional($payroll->user)->surname,
                    'Start Date'              => $payroll->start_date,
                    'End Date'                => $payroll->end_date,
                    'Normal Hours'            => $payroll->normal_hours,
                    'Overtime Hours'          => $payroll->overtime,
                    'Public Holiday Hours'    => $payroll->public_holidays,
                    'Gross Salary Earned'     => $payroll->gross_salary_earned,
                    'NIS'                     => $payroll->less_nis,
                    'NHT'                     => $payroll->nht,
                    'PAYE'                    => $payroll->paye,
                    'Education Tax'           => $payroll->education_tax,
                    'Net Salary'              => $payroll->statutory_income,
                    'Staff Loan'              => $payroll->staff_loan,
                    'Medical Insurance'       => $payroll->medical_insurance,
                    'Salary Advance'          => $payroll->salary_advance,
                    'Approved Pension Scheme' => $payroll->approved_pension_scheme,
                    'PSRA'                    => $payroll->psra,
                    'Bank Loan'               => $payroll->bank_loan,
                    'Garnishment'             => $payroll->garnishment,
                    'Missing Goods'           => $payroll->missing_goods,
                    'Damaged Goods'           => $payroll->damaged_goods,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Guard Name',
            'Start Date',
            'End Date',
            'Normal Hours',
            'Overtime Hours',
            'Public Holiday Hours',
            'Gross Salary Earned',
            'NIS',
            'NHT',
            'PAYE',
            'Education Tax',
            'Net Salary',
            'Staff Loan',
            'Medical Insurance',
            'Salary Advance',
            'Approved Pension Scheme',
            'PSRA',
            'Bank Loan',
            'Garnishment',
            'Missing Goods',
            'Damaged Goods',
        ];
    }
}
