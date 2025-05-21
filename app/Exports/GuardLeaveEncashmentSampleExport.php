<?php 
namespace App\Exports;

use App\Models\User;
use App\Models\Leave;
use App\Models\GuardLeaveEncashment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuardLeaveEncashmentSampleExport implements FromArray, WithHeadings
{
    protected $annualLeaveQuota = 10;
public function array(): array
{
    $data = [];
    $guardRole = \Spatie\Permission\Models\Role::find(3);

    $guards = User::where('status', 'Active')
        ->whereHas('roles', function ($query) use ($guardRole) {
            $query->where('role_id', $guardRole->id);
        })->get();

    foreach ($guards as $guard) {
        $usedLeaves = Leave::where('guard_id', $guard->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->count();

        $encashedLeaves = GuardLeaveEncashment::where('guard_id', $guard->id)->sum('encash_leaves');
        $pendingLeaves = max(0, $this->annualLeaveQuota - $usedLeaves - $encashedLeaves);

        $data[] = [
            'guard_id'        => $guard->id,
            'pending_leaves'  => number_format((float) $pendingLeaves, 1),
            'encash_leaves'   => number_format((float) $encashedLeaves, 1),
        ];
    }

    return $data;
}

    public function headings(): array
    {
        return [
            'guard_id',
            'pending_leaves',
            'encash_leaves',
        ];
    }
}
