<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Leave;
use App\Models\GuardLeaveEncashment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\GuardLeaveEncashmentImport;
use App\Exports\GuardLeaveEncashmentResultExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GuardLeaveEncashmentSampleExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use PDF;


class GuardLeaveEncashmentController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view guard encashment')) {
            abort(403);
        }

        $query = GuardLeaveEncashment::with('guardUser');

        if ($request->filled('guard_id')) {
            $query->where('guard_id', $request->guard_id);
        }

        $encashments = $query->latest()->get();

        $userRole = Role::find(3); // Security Guard role
        $guards = User::whereHas('roles', function ($q) use ($userRole) {
            $q->where('role_id', $userRole->id);
        })->where('status', 'Active')->get();

        return view('admin.guard-leave-encashment.index', compact('encashments', 'guards'));
    }


    public function create()
    {
        if (!Gate::allows('create guard encashment')) {
            abort(403);
        }

        $userRole = Role::find(3);

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.guard-leave-encashment.create', compact('guards'));
    }


    public function store(Request $request)
    {
        if (!Gate::allows('create guard encashment')) {
            abort(403);
        }

        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
            'pending_leaves' => 'required|numeric|min:0',
        ]);

        if ($request->encash_leaves > $request->pending_leaves) {
            return redirect()->back()->withInput()->withErrors([
                'encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $request->pending_leaves . ').'
            ]);
        }

        GuardLeaveEncashment::create([
            'guard_id' => $request->guard_id,
            'pending_leaves' => $request->pending_leaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('guard-leave-encashment.index')->with('success', 'Guard Leave Encashment recorded.');
    }

    public function edit($id)
    {
        if (!Gate::allows('edit guard encashment')) {
            abort(403);
        }

        $encashment = GuardLeaveEncashment::with('guardUser')->findOrFail($id);

        $userRole = Role::find(3); // or Role::where('name', 'Security Guard')->first()

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.guard-leave-encashment.edit', compact('encashment', 'guards'));
    }


    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit guard encashment')) {
            abort(403);
        }

        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
            'pending_leaves' => 'required|numeric|min:0',
        ]);

        if ($request->encash_leaves > $request->pending_leaves) {
            return redirect()->back()
                ->withErrors(['encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $request->pending_leaves . ').'])
                ->withInput();
        }

        $encashment = GuardLeaveEncashment::findOrFail($id);

        $encashment->update([
            'guard_id' => $request->guard_id,
            'pending_leaves' => $request->pending_leaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('guard-leave-encashment.index')->with('success', 'Guard Leave Encashment updated.');
    }

    public function destroy($id)
    {
        if (!Gate::allows('delete guard encashment')) {
            abort(403);
        }

        GuardLeaveEncashment::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guard leave encashment record deleted successfully!'
        ]);
    }

    public function getPendingLeaves(Request $request)
    {
        $guardId = $request->guard_id;

        if (!$guardId) {
            return response()->json(['pending_leaves' => 0]);
        }

        $userRole = Role::find(3);

        $guard = User::where('id', $guardId)
            ->where('status', 'Active')
            ->whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })->first();

        if (!$guard) {
            return response()->json(['pending_leaves' => 0]);
        }

        $usedLeaves = Leave::where('guard_id', $guard->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->where('leave_type', 'Vacation Leave')
            ->count();

        $encashedLeaves = DB::table('guard_leave_encashments')
            ->where('guard_id', $guardId)
            ->sum('encash_leaves');
        // dd( $usedLeaves);
        $pendingLeaves = max(0, 10 - $usedLeaves - $encashedLeaves);

        return response()->json(['pending_leaves' => $pendingLeaves]);
    }

    // public function getPendingLeaves(Request $request)
    // {
    //     $guardId = $request->guard_id;

    //     if (!$guardId) {
    //         return response()->json(['pending_leaves' => 0]);
    //     }

    //     $userRole = Role::find(3);

    //     $guard = User::where('id', $guardId)
    //         ->where('status', 'Active')
    //         ->whereHas('roles', function ($query) use ($userRole) {
    //             $query->where('role_id', $userRole->id);
    //         })->first();

    //     if (!$guard) {
    //         return response()->json(['pending_leaves' => 0]);
    //     }

    //     $yearlyLeaves = (int) setting('yearly_leaves') ?: 10;
    //     $carryForwardLimit = 10; // You can adjust this if needed

    //     $now = now();
    //     $currentYear = $now->year;
    //     $previousYear = $currentYear - 1;

    //     // Step 1: Leaves used in previous year
    //     $usedLastYear = Leave::where('guard_id', $guardId)
    //         ->where('status', 'approved')
    //         ->whereYear('date', $previousYear)
    //         ->get()
    //         ->sum(function ($leave) {
    //             return $leave->type === 'Half Day' ? 0.5 : 1;
    //         });

    //     // Step 2: Carry Forward Leaves
    //     $carryForward = min(max(0, $yearlyLeaves - $usedLastYear), $carryForwardLimit);

    //     // Step 3: Total available leaves this year
    //     $totalAvailable = $yearlyLeaves + $carryForward;

    //     // Step 4: Leaves used this year
    //     $usedThisYear = Leave::where('guard_id', $guardId)
    //         ->where('status', 'approved')
    //         ->whereYear('date', $currentYear)
    //         ->get()
    //         ->sum(function ($leave) {
    //             return $leave->type === 'Half Day' ? 0.5 : 1;
    //         });

    //     // Step 5: Leaves encashed this year
    //     $encashedLeaves = DB::table('guard_leave_encashments')
    //         ->where('guard_id', $guardId)
    //         ->whereYear('created_at', $currentYear)
    //         ->sum('encash_leaves');

    //     // Step 6: Final pending leaves
    //     $pendingLeaves = max(0, $totalAvailable - $usedThisYear - $encashedLeaves);

    //     return response()->json(['pending_leaves' => $pendingLeaves]);
    // }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,csv',
        ]);

        try {
            $file = $request->file('import_file');
            $importHandler = new GuardLeaveEncashmentImport();

            Excel::import($importHandler, $file);
            $results = $importHandler->getResults();

            $successCount = collect($results)->where('status', 'Success')->count();
            $failCount = collect($results)->where('status', 'Failed')->count();

            Session::flash('message', "$successCount rows imported successfully. $failCount rows failed.");
            Session::flash('alert-type', $failCount > 0 ? 'warning' : 'success');

            $filename = 'guard_leave_encashment_result_' . now()->format('Ymd_His') . '.xlsx';
            return Excel::download(new GuardLeaveEncashmentResultExport($results), $filename);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'message' => 'Import failed: ' . $e->getMessage(),
                'alert-type' => 'error'
            ]);
        }
    }

    public function downloadSample()
    {
        return Excel::download(new GuardLeaveEncashmentSampleExport, 'guard_leave_encashment_sample.xlsx');
    }
}
