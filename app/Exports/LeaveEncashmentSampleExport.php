<?php

namespace App\Exports;

use App\Models\User;
use App\Models\EmployeeLeave;
use App\Models\LeaveEncashment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveEncashmentSampleExport implements FromArray, WithHeadings
{
    protected $annualLeaveQuota = 10;

    public function array(): array
    {
        $data = [];

        $employees = User::role('employee')->get();

        foreach ($employees as $employee) {


            $usedLeaves = 0;
            $encashedLeaves = 0;


            $leaves = EmployeeLeave::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('date', '>=', now()->subYear())
                ->get();

            if (!$leaves->isEmpty()) {
                $usedLeaves = $leaves->sum(function ($leave) {
                    return $leave->type === 'Half Day' ? 0.5 : 1;
                });
            }


            $encashedLeaves = LeaveEncashment::where('employee_id', $employee->id)
                ->sum('encash_leaves');
            $encashedLeaves = $encashedLeaves ?? 0;


            $pendingLeaves = max(0, $this->annualLeaveQuota - $usedLeaves - $encashedLeaves);


            $data[] = [
                'employee_id'    => $employee->id,
                'pending_leaves' => number_format((float) $pendingLeaves, 1),
                'encash_leaves'  => number_format((float) $encashedLeaves, 1),
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'employee_id',
            'pending_leaves',
            'encash_leaves',
        ];
    }
}
