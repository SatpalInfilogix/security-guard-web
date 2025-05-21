<?php

namespace App\Imports;

use App\Models\LeaveEncashment;
use App\Models\EmployeeLeave;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeaveEncashmentImport implements ToCollection, WithHeadingRow
{
    protected $results = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; 
            $data = array_map('trim', $row->toArray());

            $employeeId = $data['employee_id'] ?? null;
            $encashLeaves = $data['encash_leaves'] ?? 0;

            if (!$employeeId) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'employee_id' => '',
                    'pending_leaves' => null,
                    'status' => 'Failed',
                    'message' => 'Missing employee_id',
                ];
                continue;
            }

          
            $usedLeaves = EmployeeLeave::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('date', '>=', now()->subYear())
                ->get()
                ->sum(function ($leave) {
                    return stripos($leave->type, 'half') !== false ? 0.5 : 1;
                });
                
            
            $encashedLeaves = LeaveEncashment::where('employee_id', $employeeId)
                ->sum('encash_leaves');

            
            $totalLeavesPerYear = 10;
            $calculatedPending = max(0, $totalLeavesPerYear - $usedLeaves - $encashedLeaves);

         
            if ($calculatedPending <= 0) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'employee_id' => $employeeId,
                    'pending_leaves' => $calculatedPending,
                    'status' => 'Skipped',
                    'message' => 'No pending leaves available.',
                ];
                continue;
            }

            $data['pending_leaves'] = $calculatedPending;

           
            $validator = Validator::make($data, [
                'employee_id'    => ['required', 'exists:users,id'],
                'pending_leaves' => ['required', 'numeric', 'min:0'],
                'encash_leaves'  => ['required', 'numeric', 'min:1'],
            ]);

            $errors = [];

            if ($validator->fails()) {
                $errors = array_merge($errors, $validator->errors()->all());
            }

            if ($encashLeaves > $calculatedPending) {
                $errors[] = 'Encashed leaves cannot be greater than pending leaves.';
            }

        
            $existing = LeaveEncashment::where('employee_id', $employeeId)
                ->where('encash_leaves', $encashLeaves)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($existing) {
                $errors[] = 'Duplicate record detected for today.';
            }

            
            if (!empty($errors)) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'employee_id' => $employeeId,
                    'pending_leaves' => $calculatedPending,
                    'status' => 'Failed',
                    'message' => implode(' ', $errors),
                ];
                continue;
            }

           
            try {
                LeaveEncashment::create([
                    'employee_id'    => $employeeId,
                    'pending_leaves' => $calculatedPending,
                    'encash_leaves'  => $encashLeaves,
                ]);

                $this->results[] = [
                    'row' => $rowNumber,
                    'employee_id' => $employeeId,
                    'pending_leaves' => $calculatedPending,
                    'status' => 'Success',
                    'message' => 'Imported successfully',
                ];
            } catch (\Exception $e) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'employee_id' => $employeeId,
                    'pending_leaves' => $calculatedPending,
                    'status' => 'Failed',
                    'message' => 'DB Error: ' . $e->getMessage(),
                ];
            }
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
