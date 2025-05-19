<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeLeave;
use App\Models\LeaveEncashment;
use Illuminate\Http\Request;

class LeaveEncashmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $encashments = LeaveEncashment::with('employee')->latest()->get();
        return view('admin.employee-leave-encashment.index', compact('encashments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::role('employee')->get();
        return view('admin.employee-leave-encashment.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
        ]);

        $employee = User::findOrFail($request->employee_id);

        $leaves = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->get();

        $usedLeaves = $leaves->sum(function ($leave) {
            return $leave->type === 'Half Day' ? 0.5 : 1;
        });

        $pendingLeaves = max(0, 10 - $usedLeaves);

        if ($request->encash_leaves > $pendingLeaves) {
            return redirect()->back()->withInput()->withErrors([
                'encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $pendingLeaves . ').'
            ]);
        }

        LeaveEncashment::create([
            'employee_id' => $employee->id,
            'pending_leaves' => $pendingLeaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('employee-leave-encashment.index')->with('success', 'Leave Encashment recorded.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveEncashment $leaveEncashment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $encashment = LeaveEncashment::with('employee')->findOrFail($id);
        $employees = User::role('employee')->get();
        return view('admin.employee-leave-encashment.edit', compact('encashment', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
        ]);

        $encashment = LeaveEncashment::findOrFail($id);
        $employee = User::findOrFail($request->employee_id);

        $leaves = EmployeeLeave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->get();

        $usedLeaves = $leaves->sum(function ($leave) {
            return $leave->type === 'Half Day' ? 0.5 : 1;
        });

        $pendingLeaves = max(0, 10 - $usedLeaves);

        if ($request->encash_leaves > $pendingLeaves) {
            return redirect()->back()
                ->withErrors(['encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $pendingLeaves . ').'])
                ->withInput();
        }

        $encashment->update([
            'pending_leaves' => $pendingLeaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('employee-leave-encashment.index')->with('success', 'Leave Encashment updated.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encashment_id)
    {
        LeaveEncashment::where('id', $encashment_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee leave encashment record deleted successfully!'
        ]);
    }

    public function getPendingLeaves(Request $request)
    {
        $employeeId = $request->employee_id;

        if (!$employeeId) {
            return response()->json(['pending_leaves' => 0]);
        }
        $leaves = EmployeeLeave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->get();

        $usedLeaves = $leaves->sum(function ($leave) {
            return $leave->type === 'Half Day' ? 0.5 : 1;
        });
        // dd( $usedLeaves);
        $pendingLeaves = max(0, 10 - $usedLeaves);

        return response()->json(['pending_leaves' => $pendingLeaves]);
    }
}
