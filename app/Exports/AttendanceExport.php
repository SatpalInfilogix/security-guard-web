<?php

namespace App\Exports;

use App\Models\Punch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithCustomCsvSettings
{
    protected $startDate;
    protected $endDate;
    protected $publicHolidays;

    public function __construct($startDate, $endDate, $publicHolidays)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->publicHolidays = $publicHolidays;
    }

    public function collection()
    {
        $attendances = Punch::with('user')
            ->whereBetween('in_time', [$this->startDate, $this->endDate])
            ->get();

        $userAttendances = $attendances->groupBy(function ($attendance) {
            return $attendance->user->id;
        });

        $attendanceData = [];

        foreach ($userAttendances as $userId => $attendanceGroup) {
            $user = $attendanceGroup->first()->user;
            $attendanceRow = [
                'guard_name' => $user->first_name . ' ' . $user->surname,  // Guard name
            ];

            $totalNormalHours = 0;
            $totalOvertimeHours = 0;
            $totalDoubleTimeHours = 0;
            $remarks = '';

            $dayColumns = [];
            $dateRange = $this->getDateRange();

            foreach ($dateRange as $index => $day) {
                $attendanceForDay = $attendanceGroup->firstWhere(function ($attendance) use ($day) {
                    return Carbon::parse($attendance->in_time)->isSameDay(Carbon::parse($day));
                });

                if ($attendanceForDay) {
                    $inTime = Carbon::parse($attendanceForDay->in_time);
                    $outTime = Carbon::parse($attendanceForDay->out_time);

                    if ($inTime && $outTime) {
                        $hoursWorked = $inTime->diffInHours($outTime);
                        $minutesWorked = $inTime->diffInMinutes($outTime) % 60;
                        $totalWorkedTime = $hoursWorked . 'h ' . $minutesWorked . 'm';

                        $dayColumns[] = $inTime->format('h:i A');
                        $dayColumns[] = $outTime->format('h:i A');
                        $dayColumns[] = $totalWorkedTime;

                        if (in_array($day, $this->publicHolidays)) {
                            $totalDoubleTimeHours += $hoursWorked;
                        } else {
                            if ($hoursWorked <= 8) {
                                $totalNormalHours += $hoursWorked;
                            } else {
                                $totalNormalHours += 8;
                                $totalOvertimeHours += ($hoursWorked - 8);
                            }
                        }

                        $remarks .= 'Attendance.';
                    }
                } else {
                    $dayColumns[] = '';
                    $dayColumns[] = '';
                    $dayColumns[] = '';
                }
            }

            $attendanceRow = array_merge($attendanceRow, $dayColumns);
            $attendanceRow[] = $totalNormalHours  . 'h';
            $attendanceRow[] = $totalOvertimeHours  . 'h';
            $attendanceRow[] = $totalDoubleTimeHours  . 'h';
            $attendanceRow[] = $remarks;

            $attendanceData[] = $attendanceRow;
        }

        return collect($attendanceData);
    }

    public function headings(): array
    {
        $headingRow1 = [
            'WEEK START:', Carbon::parse($this->startDate)->format('d/m/Y'),
            'WEEK END:', Carbon::parse($this->endDate)->format('d/m/Y')
        ];
        $headings = [
            'Guard Name',
        ];

        $dateRange = $this->getDateRange();
        foreach ($dateRange as $index => $day) {
            $headings[] = "Time-in";
            $headings[] = "Time-out";
            $headings[] = "Hours";
        }

        $headings[] = 'Normal';
        $headings[] = 'Overtime Hours';
        $headings[] = 'Public Holidays Hours';
        $headings[] = 'Remarks';

        return [
            $headingRow1,
            $headings,
        ];
    }

    private function getDateRange()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $dateRange = [];

        while ($startDate <= $endDate) {
            $dateRange[] = $startDate->toDateString();
            $startDate->addDay();
        }

        return $dateRange;
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
