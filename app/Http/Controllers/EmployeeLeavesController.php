<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeLeave;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmployeeLeavesController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view employee leaves')) {
            abort(403);
        }
        $page = $request->input('page', 1);
        $leaveStatus = $request->input('leave_status');
        $month = $request->input('month');
        $year = $request->input('year');
        return view('admin.employee-leaves.index', compact(
            'page',
            'leaveStatus',
            'month',
            'year'
        ));
    }

    public function create()
    {
        if (!Gate::allows('create employee leaves')) {
            abort(403);
        }
        $userRole = Role::where('id', 9)->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.employee-leaves.create', compact('employees'));
    }

    public function getEmployeeLeaves(Request $request)
    {
        $query = EmployeeLeave::select(
            'employee_id',
            'batch_id',
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(leave_type) as leave_type'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description')
        )
        ->groupBy('employee_id', 'batch_id')
        ->with('user');

        // ✅ Filter by status
        if ($request->filled('leave_status')) {
            $query->where('status', $request->leave_status);
        }

        // ✅ Filter by month
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // ✅ Filter by year
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        // ✅ Search filter
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('date', 'like', '%' . $searchValue . '%')
                ->orWhere('reason', 'like', '%' . $searchValue . '%')
                ->orWhere('leave_type', 'like', '%' . $searchValue . '%')
                ->orWhereHas('user', function ($q2) use ($searchValue) {
                    $q2->where('first_name', 'like', '%' . $searchValue . '%');
                });
            });
        }

        // Get total records count before pagination
        $totalRecords = EmployeeLeave::select('employee_id', 'batch_id')
        ->groupBy('employee_id', 'batch_id')
        ->get()
        ->count();

        // ✅ Pagination
        $countQuery = (clone $query);
        // $totalRecords = $countQuery->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
        $leaves = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $leaves,
        ]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create employee leaves')) {
            abort(403);
        }

        $request->validate([
            'employee_id'         => 'required',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'leave_type'          => 'required|string|in:Sick Leave,Vacation Leave,Maternity Leave',
            'reason'              => 'nullable|string',
            'description'         => 'nullable|string',
            'actual_start_date'   => 'nullable|date',
            'actual_end_date'     => 'nullable|date|after_or_equal:actual_start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate;

        if ($endDate->lt($startDate)) {
            return redirect()->route('employee-leaves.index')->with('error', 'End date cannot be earlier than start date.');
        }

        $dates = $startDate->toPeriod($endDate, '1 day');
        $conflictingDates = [];
        $createdDates = [];

        // ✅ Generate unique batch ID
        $batchId = Str::uuid();

        foreach ($dates as $date) {
            $exists = EmployeeLeave::where('employee_id', $request->employee_id)
                ->whereDate('date', $date)
                ->exists();

            if ($exists) {
                $conflictingDates[] = $date->toDateString();
            } else {
                EmployeeLeave::create([
                    'employee_id'        => $request->employee_id,
                    'date'               => $date,
                    'reason'             => $request->reason,
                    'leave_type'         => $request->leave_type,
                    'description'        => $request->description,
                    'actual_start_date'  => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                    'actual_end_date'    => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                    'batch_id'           => $batchId, // ✅ for grouping/editing later
                ]);

                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('employee-leaves.index')
                ->with('error', 'Employee Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('employee-leaves.index')
            ->with('success', 'Employee Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }

    public function destroy($employeeId, $batchId = null)
    {
        if (!Gate::allows('delete employee leaves')) {
            abort(403);
        }

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID is required for deletion.'
            ], 400);
        }

        $deleted = EmployeeLeave::where('employee_id', $employeeId)
            ->where('batch_id', $batchId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No leave records found for the provided batch ID.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee leave deleted successfully.'
        ]);
    }

    public function updateLeaveStatus($employeeId, Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'batch_id' => 'required|uuid',
            'rejection_reason' => 'nullable|string'
        ]);

        $batchId = $request->batch_id;

        // Fetch all employee leaves in the batch
        $leaves = EmployeeLeave::where('employee_id', $employeeId)
            ->where('batch_id', $batchId)
            ->get();

        if ($leaves->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee leave records not found for this batch.'
            ], 404);
        }

        // Rejection reason check
        if ($request->status === 'Rejected' && empty($request->rejection_reason)) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required for rejected leaves.'
            ], 400);
        }

        foreach ($leaves as $leave) {
            $leave->status = $request->status;

            if ($request->status === 'Rejected') {
                $leave->rejection_reason = $request->rejection_reason;
            }

            $leave->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee leave status updated successfully.',
            'status' => $request->status,
            'employeeId' => $employeeId,
            'batchId' => $batchId
        ]);
    }

    public function edit($employeeId, $batchId)
    {
        if (!Gate::allows('edit employee leaves')) {
            abort(403);
        }

        // Fetch all leaves for the given employee and batch
        $leaves = EmployeeLeave::where('employee_id', $employeeId)
            ->where('batch_id', $batchId)
            ->with('user')
            ->orderBy('date')
            ->get();

        if ($leaves->isEmpty()) {
            abort(404, 'Employee leave records not found for the specified employee and batch.');
        }

        // Create a pseudo-object similar to guard leave edit
        $leave = new \stdClass();
        $leave->employee_id = $employeeId;
        $leave->batch_id = $batchId;
        $leave->start_date = $leaves->min('date');
        $leave->end_date = $leaves->max('date');
        $leave->status = $leaves->min('status');
        $leave->reason = $leaves->min('reason');
        $leave->actual_start_date = $leaves->min('actual_start_date');
        $leave->actual_end_date = $leaves->min('actual_end_date');
        $leave->description = $leaves->min('description');
        $leave->leave_type = $leaves->min('leave_type');
        $leave->user = $leaves->first()->user;

        // Collect individual dates for frontend handling
        $leaveDates = $leaves->pluck('date')->toArray();

        // Get active employees with role ID 9
        $employeeRole = Role::find(9);
        $employees = User::with('guardAdditionalInformation')
            ->whereHas('roles', function ($query) use ($employeeRole) {
                $query->where('role_id', $employeeRole->id);
            })
            ->where('status', 'Active')
            ->latest()
            ->get();

        return view('admin.employee-leaves.edit', [
            'leave' => $leave,
            'leaveDates' => $leaveDates,
            'employees' => $employees
        ]);
    }

    public function update(Request $request, $employeeId, $batchId)
    {
        if (!Gate::allows('edit employee leaves')) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'leave_type' => 'required|string|in:Sick Leave,Vacation Leave,Maternity Leave',
            'type' => 'required|string',
            'reason' => 'required|string',
            'description' => 'nullable|string',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
        ]);

        try {
            DB::beginTransaction();

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : $startDate;

            if ($endDate->lt($startDate)) {
                return back()->with('error', 'End date cannot be earlier than start date.');
            }

            // Fetch the original leave group by employee and batch
            $originalLeaves = EmployeeLeave::where('employee_id', $employeeId)
                ->where('batch_id', $batchId)
                ->get();

            if ($originalLeaves->isEmpty()) {
                return back()->with('error', 'Original employee leave records not found.');
            }

            $originalCreatedAt = $originalLeaves->first()->created_at;
            $originalStatus = $originalLeaves->first()->status;

            // Delete existing leave records in this batch
            EmployeeLeave::where('employee_id', $employeeId)
                ->where('batch_id', $batchId)
                ->delete();

            // Recreate leave entries with same batch ID and preserved data
            $period = Carbon::parse($startDate)->toPeriod($endDate);

            foreach ($period as $date) {
                EmployeeLeave::create([
                    'employee_id' => $employeeId,
                    'date' => $date->format('Y-m-d'),
                    'type' => $validated['type'],
                    'leave_type' => $validated['leave_type'],
                    'reason' => $validated['reason'],
                    'description' => $validated['description'],
                    'actual_start_date' => $validated['actual_start_date'] ? Carbon::parse($validated['actual_start_date']) : null,
                    'actual_end_date' => $validated['actual_end_date'] ? Carbon::parse($validated['actual_end_date']) : null,
                    'batch_id' => $batchId,
                    'status' => $originalStatus,
                    'created_at' => $originalCreatedAt,
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('employee-leaves.index')
                ->with('success', 'Employee leave updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating employee leave: ' . $e->getMessage());
        }
    }
}
