<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeNSTDeductionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $deductionDetails;

    public function __construct($deductionDetails)
    {
        $this->deductionDetails = $deductionDetails;
    }

    public function collection()
    {
        return $this->deductionDetails;
    }

    public function headings(): array
    {
        return [
            'Employee No',
            'Employee Name',
            'NST Type',
            'Payroll Date',
            'Amount to be Deducted',
            'Amount Deducted',
            'Balance Outstanding'
        ];
    }

    public function map($deductionDetails): array
    {
        return [
            $deductionDetails->deduction->user->user_code ?? '',
            $deductionDetails->deduction->user->first_name . ' ' . $deductionDetails->deduction->user->surname,
            $deductionDetails->deduction->type ?? '',
            $deductionDetails->deduction_date
                ? Carbon::parse($deductionDetails->deduction_date)->format('d-m-Y')
                : 'N/A',
            formatAmount($deductionDetails->deduction->one_installment ?? 0),
            formatAmount($deductionDetails->amount_deducted ?? 0),
            formatAmount($deductionDetails->deduction->pending_balance ?? 0)
        ];
    }
}
