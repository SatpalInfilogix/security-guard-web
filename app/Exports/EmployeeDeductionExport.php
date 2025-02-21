<?php

namespace App\Exports;

use App\Models\Deduction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class EmployeeDeductionExport implements  FromCollection, WithHeadings, WithMapping
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
            'Non Stat Deduction',
            'Amount',
            'No Of Deduction',
            'Document Date',
            'Date Deducted',
            'Amount Deducted',
            'Balance'
        ];
    }

    public function map($deductionDetails): array
    {
        return [
            $deductionDetails->deduction->user->user_code,
            $deductionDetails->deduction->user->first_name,
            $deductionDetails->deduction->type,
            $deductionDetails->deduction->amount,
            $deductionDetails->deduction->no_of_payroll,
            $deductionDetails->deduction->document_date ? Carbon::parse($deductionDetails->deduction->document_date) : 'N/A',
            $deductionDetails->deduction_date ? Carbon::parse($deductionDetails->deduction_date)->format('d-m-Y') : 'N/A',
            $deductionDetails->amount_deducted,
            $deductionDetails->balance,
        ];
    }
}
