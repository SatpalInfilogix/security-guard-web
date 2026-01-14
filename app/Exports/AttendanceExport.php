<?php

namespace App\Exports;

use App\Models\Punch;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

class AttendanceExport implements  WithCustomCsvSettings, WithStyles
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

    // public function attendanceData()
    // {
    //     $attendances = Punch::with('user')
    //         ->whereBetween('in_time', [$this->startDate, $this->endDate])
    //         ->get();

    //     $userAttendances = $attendances->groupBy(function ($attendance) {
    //         return $attendance->user->id;
    //     });

    //     $attendanceData = [];
    //     // Accumulated totals for all users
    //     $totalNormalHoursAllUsers = 0;
    //     $totalOvertimeMinutesAllUsers = 0;  // Track overtime in minutes
    //     $totalDoubleTimeHoursAllUsers = 0;

    //     foreach ($userAttendances as $userId => $attendanceGroup) {
    //         $user = $attendanceGroup->first()->user;
    //         $attendanceRow = [
    //             $user->first_name . ' ' . $user->surname,
    //         ];

    //         $totalNormalHours = 0;
    //         $totalOvertimeMinutes = 0;  // Track overtime minutes
    //         $totalDoubleTimeHours = 0;
    //         $remarks = '';

    //         $dateRange = $this->getDateRange();
    //         foreach ($dateRange as $day) {
    //             $attendanceForDay = $attendanceGroup->firstWhere(function ($attendance) use ($day) {
    //                 return Carbon::parse($attendance->in_time)->isSameDay(Carbon::parse($day));
    //             });

    //             if ($attendanceForDay) {
    //                 $inTime = Carbon::parse($attendanceForDay->in_time);
    //                 $outTime = Carbon::parse($attendanceForDay->out_time);

    //                 if ($inTime && $outTime) {
    //                     // Total minutes worked
    //                     $totalMinutes = $inTime->diffInMinutes($outTime);
    //                     $hoursWorked = floor($totalMinutes / 60);
    //                     $minutesWorked = $totalMinutes % 60;
    //                     $totalWorkedTime = $hoursWorked . 'h ' . $minutesWorked . 'm';

    //                     // Add worked time to row
    //                     $attendanceRow[] = $inTime->format('h:i A');
    //                     $attendanceRow[] = $outTime->format('h:i A');
    //                     $attendanceRow[] = $totalWorkedTime;

    //                     // If it's a public holiday, count double time hours
    //                     if (in_array($day, $this->publicHolidays)) {
    //                         $totalDoubleTimeHours += $hoursWorked;
    //                     } else {
    //                         // Normal hours calculation
    //                         if ($hoursWorked <= 8) {
    //                             $totalNormalHours += $hoursWorked; // normal hours within 8 hours
    //                         } else {
    //                             $totalNormalHours += 8; // cap normal hours at 8
    //                             // Overtime calculation in minutes
    //                             $overtimeMinutes = $totalMinutes - (8 * 60); // Calculate the overtime minutes
    //                             $totalOvertimeMinutes += $overtimeMinutes;  // Add overtime minutes
    //                         }
    //                     }

    //                     // Remarks
    //                     $remarks .= 'Attendance; ';
    //                 }
    //             } else {
    //                 $attendanceRow[] = '';
    //                 $attendanceRow[] = '';
    //                 $attendanceRow[] = '';
    //             }
    //         }

    //         // Convert total overtime minutes to hours and minutes
    //         $overtimeHours = floor($totalOvertimeMinutes / 60);
    //         $overtimeMinutes = $totalOvertimeMinutes % 60;

    //         // Add the totals for the current user
    //         $attendanceRow[] = $totalNormalHours . 'h';
    //         $attendanceRow[] = $overtimeHours . 'h ' . $overtimeMinutes . 'm';  // Overtime in "h m" format
    //         $attendanceRow[] = $totalDoubleTimeHours . 'h';
    //         $attendanceRow[] = $remarks;

    //         // Accumulate totals for all users
    //         $totalNormalHoursAllUsers += $totalNormalHours;
    //         $totalOvertimeMinutesAllUsers += $totalOvertimeMinutes;
    //         $totalDoubleTimeHoursAllUsers += $totalDoubleTimeHours;

    //         // Add this user’s row to the attendance data
    //         $attendanceData[] = $attendanceRow;
    //     }

    //     // Return the collection without the total row at the end
    //     return collect($attendanceData);
    // }

    public function attendanceData()
    {
        $attendances = Punch::with('user')
            ->whereBetween('in_time', [$this->startDate, $this->endDate])
            ->get();
    
        $userAttendances = $attendances->groupBy(function ($attendance) {
            return $attendance->user->id;
        });
    
        $attendanceData = [];
        $totalNormalHoursAllUsers = 0;
        $totalOvertimeMinutesAllUsers = 0;
        $totalDoubleTimeHoursAllUsers = 0;
    
        foreach ($userAttendances as $userId => $attendanceGroup) {
            $user = $attendanceGroup->first()->user;
            $attendanceRow = [
                $user->first_name . ' ' . $user->surname,
            ];
    
            $totalNormalHours = 0;
            $totalOvertimeMinutes = 0;
            $totalDoubleTimeHours = 0;
            $dateRange = $this->getDateRange();
            foreach ($dateRange as $day) {
                $attendanceForDay = $attendanceGroup->filter(function ($attendance) use ($day) {
                    return Carbon::parse($attendance->in_time)->isSameDay(Carbon::parse($day));
                });
    
                $timeInList = [];
                $timeOutList = [];
                $workedTimeList = [];
                $totalWorkedMinutes = 0;
                
                if ($attendanceForDay->isNotEmpty()) {
                    foreach ($attendanceForDay as $attendance) {
                        $inTime = Carbon::parse($attendance->in_time);
                        $outTime = Carbon::parse($attendance->out_time);
    
                        $timeInList[] = $inTime->format('m/d/Y h:i A');
                        $timeOutList[] = $outTime->format('m/d/Y h:i A');
                        
                        $workedMinutes = $inTime->diffInMinutes($outTime);
                        $workedHours = floor($workedMinutes / 60);
                        $remainingMinutes = $workedMinutes % 60;
                        $workedTime = $workedHours . 'h ' . $remainingMinutes . 'm';
    
                        $workedTimeList[] = $workedTime;
    
                        $totalWorkedMinutes += $workedMinutes;
                    }
    
                    $hoursWorked = floor($totalWorkedMinutes / 60);
                    $minutesWorked = $totalWorkedMinutes % 60;
                    $totalWorkedTimeForDay = $hoursWorked . 'h ' . $minutesWorked . 'm';
    
                    $attendanceRow[] = implode(', ', $timeInList);
                    $attendanceRow[] = implode(', ', $timeOutList);
                    $attendanceRow[] = implode(', ', $workedTimeList);
    
                    if (in_array($day, $this->publicHolidays)) {
                        $totalDoubleTimeHours += $hoursWorked;
                    } else {
                        if ($hoursWorked <= 8) {
                            $totalNormalHours += $hoursWorked;
                        } else {
                            $totalNormalHours += 8;
                            $overtimeMinutes = $totalWorkedMinutes - (8 * 60);
                            $totalOvertimeMinutes += $overtimeMinutes;
                        }
                    }
                } else {
                    $attendanceRow[] = '';
                    $attendanceRow[] = '';
                    $attendanceRow[] = '';
                }
            }

            $overtimeHours = floor($totalOvertimeMinutes / 60);
            $overtimeMinutes = $totalOvertimeMinutes % 60;

            $attendanceRow[] = $totalNormalHours . 'h';
            $attendanceRow[] = $overtimeHours . 'h ' . $overtimeMinutes . 'm';
            $attendanceRow[] = $totalDoubleTimeHours . 'h';
            $attendanceRow[] = 'Attendance;';

            $totalNormalHoursAllUsers += $totalNormalHours;
            $totalOvertimeMinutesAllUsers += $totalOvertimeMinutes;
            $totalDoubleTimeHoursAllUsers += $totalDoubleTimeHours;

            $attendanceData[] = $attendanceRow;
        }

        $totalOvertimeHoursAllUsers = floor($totalOvertimeMinutesAllUsers / 60);
        $totalOvertimeMinutesAllUsers = $totalOvertimeMinutesAllUsers % 60;

        return collect($attendanceData);
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
            'enclosure' => '',
            'escape' => '\\',
        ];
    }

    public function styles($sheet)
    {
        $styleHeader = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
        ];

        $sheet->setCellValue('B1', 'VANGUARD SECURITY LIMITED');
        $sheet->mergeCells('B1:J1');
        $sheet->getStyle('B1:J1')->applyFromArray($styleHeader);

        $sheet->setCellValue('L1', 'Vanguard Expense');
        $sheet->mergeCells('L1:O1');
        $sheet->getStyle('L1:O1')->applyFromArray($styleHeader);

        $sheet->setCellValue('L2', 'Normal');
        $sheet->mergeCells('L2:O2');

        $sheet->setCellValue('L3', 'Overtimes');
        $sheet->mergeCells('L3:O3');

        $sheet->setCellValue('L4', 'Public Holidays');
        $sheet->mergeCells('L4:O4');

        $sheet->setCellValue('P1', 'Client Billing');
        $sheet->mergeCells('P1:S1');
        $sheet->getStyle('P1:S1')->applyFromArray($styleHeader);

        $sheet->setCellValue('P2', 'Normal');
        $sheet->mergeCells('P2:S2');

        $sheet->setCellValue('P3', 'Overtimes');
        $sheet->mergeCells('P3:S3');

        $sheet->setCellValue('P4', 'Public Holidays');
        $sheet->mergeCells('P4:S4');

        $sheet->setCellValue('B2', '6 EASTWOOD AVENUE, KINGSTON 10');
        $sheet->mergeCells('B2:J2');
        $sheet->getStyle('B2:J2')->applyFromArray($styleHeader);

        $sheet->setCellValue('B4', 'ATTENDANCE SHEET cum TIMESHEET');
        $sheet->mergeCells('B4:J4');
        $sheet->getStyle('B4:J4')->applyFromArray($styleHeader);

        $sheet->setCellValue('A6', 'LOCATION');
        $sheet->setCellValue('C6', 'WEEK START:');
        $sheet->setCellValue('F6', Carbon::parse($this->startDate)->format('d/m/Y'));
        $sheet->setCellValue('I6', 'WEEK END:');
        $sheet->setCellValue('M6', Carbon::parse($this->endDate)->format('d/m/Y'));

        $sheet->getStyle('A6:E6')->applyFromArray($styleHeader);
        $sheet->getStyle('F6:H6')->applyFromArray($styleHeader);
        $sheet->getStyle('I6:L6')->applyFromArray($styleHeader);
        $sheet->getStyle('M6:P6')->applyFromArray($styleHeader);

        $headings = ['Guard Name'];

        foreach ($this->getDateRange() as $day) {
            $headings[] = 'Time-in';
            $headings[] = 'Time-out';
            $headings[] = 'Hours';
        }

        $headings[] = 'Normal';
        $headings[] = 'Overtime Hours';
        $headings[] = 'Public Holidays Hours';
        $headings[] = 'Remarks';

        $sheet->fromArray($headings, null, 'A7');
        $sheet->getStyle('A7:BZ7')->applyFromArray($styleHeader);

        $attendanceData = $this->attendanceData()->toArray();
        $sheet->fromArray($attendanceData, null, 'A8');
        
        $sheet->mergeCells('A' . (8 + count($attendanceData) + 2) . ':J' . (8 + count($attendanceData) + 2));
        $sheet->mergeCells('K' . (8 + count($attendanceData) + 2) . ':V' . (8 + count($attendanceData) + 2));

        $sheet->setCellValue('A' . (8 + count($attendanceData) + 2), 'Client Supervisor');
        $sheet->getStyle('A' . (8 + count($attendanceData) + 2))->getFont()->setBold(true);
       
        $sheet->setCellValue('K' . (8 + count($attendanceData) + 2), 'Vanguard Supervisor');
        $sheet->getStyle('K' . (8 + count($attendanceData) + 2))->getFont()->setBold(true);

        $sheet->setCellValue('A' . (8 + count($attendanceData) + 3), 'Name');
        $sheet->setCellValue('K' . (8 + count($attendanceData) + 3), 'Name');
        
        $sheet->setCellValue('A' . (8 + count($attendanceData) + 4), 'Signature');
        $sheet->setCellValue('K' . (8 + count($attendanceData) + 4), 'Signature');
        
        $sheet->setCellValue('A' . (8 + count($attendanceData) + 5), 'Date');
        $sheet->setCellValue('K' . (8 + count($attendanceData) + 5), 'Date');

        $sheet->getStyle('A' . (8 + count($attendanceData) + 2) . ':B' . (8 + count($attendanceData) + 5))
            ->applyFromArray([
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
            ]);

        return [];
    }
    
}
