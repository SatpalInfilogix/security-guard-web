<?php

namespace App\Exports;

use App\Models\PunchTable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class AttendanceExport implements FromCollection, WithHeadings, WithCustomCsvSettings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $attendances = PunchTable::with('user')->latest()->get()
            ->map(function ($attendance) {
                return [
                    'first_name' => $attendance->user->first_name ?? 'N/A',
                    'middle_name' => $attendance->user->middle_name ?? 'N/A',
                    'surname' => $attendance->user->surname ?? 'N/A',
                    'in_time' => $attendance->in_time,
                    'out_time' => $attendance->out_time,
                ];
            });

        return $attendances;
    }

    public function headings(): array
    {
        return [
            'first_name',
            'middle_name',
            'surname',
            'in_time',
            'out_time'
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '', // Set enclosure to empty string to avoid quotes
            'escape' => '\\',
        ];
    }
}
