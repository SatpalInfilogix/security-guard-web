<?php 
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveEncashmentImportResultExport implements FromCollection, WithHeadings
{
    protected $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        return new Collection($this->results);
    }

    public function headings(): array
    {
        return ['Row', 'Employee ID', 'Status', 'Message'];
    }
}

