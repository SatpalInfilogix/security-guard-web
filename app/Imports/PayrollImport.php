<?php

namespace App\Imports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayrollImport implements ToModel, WithHeadingRow
{
    protected $importResults = [];
    protected $rowIndex = 0;

    public function model(array $row)
    {
        $this->rowIndex++;

        $validator = Validator::make($row, [
            'guard_id'          => 'required',
            'start_date'        => 'required|date_format:d-m-Y',
            'end_date'          => 'required|date_format:d-m-Y',
            'approved_pension_scheme' => 'nullable|numeric',
            'paye'              => 'nullable|numeric',
            'staff_loan'        => 'nullable|numeric',
            'medical_insurance' => 'nullable|numeric',
            'threshold'         => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            $this->importResults[] = [
                'row_index' => $this->rowIndex,
                'guard_id'  => $row['guard_id'],
                'status' => 'Failed',
                'failure_reason' => $validator->errors()->toArray(),
                'row' => $row
            ];
            return null;
        }

        $startDate = Carbon::createFromFormat('d-m-Y', $row['start_date']);
        $endDate = Carbon::createFromFormat('d-m-Y', $row['end_date']);

        $existingRoster = Payroll::where('guard_id', $row['guard_id'])
                                  ->where('start_date', $startDate->format('Y-m-d'))
                                  ->where('end_date', $endDate->format('Y-m-d'))
                                  ->where('is_publish', 0)
                                  ->first();

        if ($existingRoster) {
            $existingRoster->update([
                'approved_pension_scheme' => $row['approved_pension_scheme'] ?? 0.00,
                'paye' => $row['paye'] ?? 0.00,
                'staff_loan' => $row['staff_loan'] ?? 0.00,
                'medical_insurance' => $row['medical_insurance'] ?? 0.00,
                'threshold' => $row['threshold'] ?? 0.00,
            ]);
            $this->importResults[] = [
                'row_index' => $this->rowIndex,
                'status' => 'Success',
                'failure_reason' => 'Updated record for Guard ID ' . $row['guard_id'],
            ];
        } else {
            $this->importResults[] = [
                'row_index' => $this->rowIndex,
                'guard_id'  => $row['guard_id'],
                'status' => 'Failed',
                'failure_reason' => 'Record not found for Guard ID ' . $row['guard_id'] . ' with the provided date range.',
                'row' => $row
            ];
        }

        return null;
    }

    public function getImportResults()
    {
        return collect($this->importResults);
    }
}
