<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Imports\SecurityGuardImport;

class GuardImportExport implements FromCollection, WithHeadings, WithTitle
{
    protected $importedData;

    public function __construct($importedData)
    {
        $this->importedData = $importedData;
    }

    public function collection()
    {
        return collect($this->importedData)->map(function ($item) {
            return [
                'Name'            => "Row". $item['row_index'] . ": ".$item['name'] ?? 'N/A', // Default to 'N/A' if name is missing
                'Status'          => $item['status'],
                'Failure Reason'  => $item['failure_reason'] ?? 'No reason', // Default failure reason if missing
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Status',
            'Failure Reason',
        ];
    }

    public function title(): string
    {
        return 'Import Results';
    }
}

