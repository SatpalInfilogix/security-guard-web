<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Imports\PayrollImport;

class PayrollExport implements FromCollection, WithHeadings, WithTitle
{
    protected $import;

    public function __construct(PayrollImport $import)
    {
        $this->import = $import;
    }

    public function collection()
    {
        return $this->import->getImportResults();
    }

    public function headings(): array
    {
        return [
            'Row',
            'Status',
            'Failure Reason',
        ];
    }

    public function title(): string
    {
        return 'Import Results';
    }
}