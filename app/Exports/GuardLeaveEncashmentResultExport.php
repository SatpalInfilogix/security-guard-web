<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuardLeaveEncashmentResultExport implements FromCollection, WithHeadings
{
    protected $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        return collect(array_map(function ($item) {
            return [
                'Row' => $item['row'] ?? '',
                'Guard ID' => $item['guard_id'] ?? '',
                'Status' => $item['status'] ?? '',
                'Message' => $item['message'] ?? '',
            ];
        }, $this->results));
    }

    public function headings(): array
    {
        return ['Row', 'Guard ID', 'Status', 'Message'];
    }
}
