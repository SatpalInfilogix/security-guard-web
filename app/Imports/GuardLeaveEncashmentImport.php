<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Leave;
use App\Models\GuardLeaveEncashment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GuardLeaveEncashmentImport implements OnEachRow, WithHeadingRow
{
    protected $results = [];

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        try {
            if (empty($rowData['guard_id']) || empty($rowData['encash_leaves'])) {
                throw new \Exception('Missing required fields: guard_id or encash_leaves.');
            }

            $guard = User::where('id', $rowData['guard_id'])
                ->where('status', 'Active')
                ->first();

            if (!$guard) {
                throw new \Exception('Guard not found or not active with ID: ' . $rowData['guard_id']);
            }

            $usedLeaves = Leave::where('guard_id', $guard->id)
                ->where('status', 'approved')
                ->where('date', '>=', now()->subYear())
                ->count();

            $encashedLeaves = GuardLeaveEncashment::where('guard_id', $guard->id)
                ->sum('encash_leaves');

            $pendingLeaves = max(0, 10 - $usedLeaves - $encashedLeaves);

            if ($rowData['encash_leaves'] > $pendingLeaves) {
                throw new \Exception("Encash leaves ({$rowData['encash_leaves']}) exceed pending leaves ($pendingLeaves).");
            }

            GuardLeaveEncashment::create([
                'guard_id' => $guard->id,
                'pending_leaves' => $pendingLeaves,
                'encash_leaves' => $rowData['encash_leaves'],
            ]);

            $this->results[] = [
                'row' => $rowIndex,
                'guard_id' => $rowData['guard_id'],
                'status' => 'Success',
                'message' => '',
            ];
        } catch (\Exception $e) {
            $this->results[] = [
                'row' => $rowIndex,
                'guard_id' => $rowData['guard_id'] ?? '',
                'status' => 'Failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
