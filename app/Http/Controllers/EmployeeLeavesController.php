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

class EmployeeLeavesController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view employee leaves')) {
            abort(403);
        }
        return view('admin.employee-leaves.index');
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
            DB::raw('DATE(created_at) as created_date'),
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(type) as type'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description')
        )
            ->groupBy('employee_id', DB::raw('DATE(created_at)'))
            ->with('user');

        if ($request->has('leave_status') && !empty($request->leave_status)) {
            $query->where('status', 'like', '%' . $request->leave_status . '%');
        }

        if ($request->has('month') && !empty($request->month)) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->has('year') && !empty($request->year)) {
            $query->whereYear('date', $request->year);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('date', 'like', '%' . $searchValue . '%')
                    ->orWhere('type', 'like', '%' . $searchValue . '%')
                    ->orWhere('reason', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q2) use ($searchValue) {
                        $q2->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $leaves = $query->skip($start)->take($length)->get();
        $totalRecords = $leaves->count();

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
            'employee_id'   => 'required',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'actual_start_date'   => 'nullable|date',
            'actual_end_date'     => 'nullable|date|after_or_equal:actual_start_date',
        ]);

        $start_date = Carbon::parse($request->start_date);
        $end_date = $request->end_date ? Carbon::parse($request->end_date) : $start_date;

        if ($end_date->lt($start_date)) {
            return redirect()->route('leaves.index')->with('error', 'End date cannot be earlier than start date.');
        }

        $dates = Carbon::parse($start_date)->toPeriod($end_date, '1 day');
        $conflictingDates = [];
        $createdDates = [];

        foreach ($dates as $date) {
            $existingLeave = EmployeeLeave::where('employee_id', $request->employee_id)->whereDate('date', $date)->exists();

            if ($existingLeave) {
                $conflictingDates[] = $date->toDateString();
            } else {
                EmployeeLeave::create([
                    'employee_id' => $request->employee_id,
                    'date'        => $date,
                    'type'        => $request->type,
                    'reason'      => $request->reason,
                    'description' => $request->description,
                    'actual_start_date' => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                    'actual_end_date'   => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('employee-leaves.index')->with('error', 'Employee Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('employee-leaves.index')->with('success', 'Employee Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }
    public function destroy($id, $date = null)
    {
        if (!Gate::allows('delete employee leaves')) {
            abort(403);
        }

        if ($date) {
            // Delete grouped leaves
            $deleted = EmployeeLeave::where('employee_id', $id)
                ->whereDate('created_at', $date)
                ->delete();
        } else {
            // Delete single leave record
            $deleted = EmployeeLeave::where('id', $id)->delete();
        }

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Employee Leave deleted successfully.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Employee Leave not found.'
        ], 404);
    }

    public function updateLeaveStatus($leaveId, Request $request)
    {
        $request->validate([
            'status' => 'required',
            'created_date' => 'nullable|date' // For grouped leaves
        ]);

        if ($request->has('created_date')) {
            $leaves = EmployeeLeave::where('employee_id', $leaveId)
                ->whereDate('created_at', $request->created_date)
                ->get();

            if ($leaves->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee Leave records not found.'
                ], 404);
            }

            foreach ($leaves as $leave) {
                $leave->status = $request->status;
                if ($request->status === 'Rejected') {
                    $leave->rejection_reason = $request->rejection_reason;
                }
                $leave->save();
            }
        } else {
            // Update status for single leave
            $leave = EmployeeLeave::find($leaveId);
            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee Leave record not found.'
                ], 404);
            }

            $leave->status = $request->status;
            if ($request->status === 'Rejected') {
                $leave->rejection_reason = $request->rejection_reason;
            }
            $leave->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    public function edit($employeeId, $date)
    {
        if (!Gate::allows('edit employee leaves')) {
            abort(403);
        }

        $leave = EmployeeLeave::select(
            'employee_id',
            DB::raw('DATE(created_at) as created_date'),
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(type) as type'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description')
        )
            ->where('employee_id', $employeeId)
            ->whereDate('created_at', $date)
            ->groupBy('employee_id', DB::raw('DATE(created_at)'))
            ->with('user')
            ->first();

        if (!$leave) {
            abort(404, 'Employee leave record not found for given user and date.');
        }

        $userRole = Role::where('id', 9)->first();
        $employees = User::with('guardAdditionalInformation')
            ->whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })
            ->where('status', 'Active')
            ->latest()
            ->get();

        return view('admin.employee-leaves.edit', compact('leave', 'employees'));
    }

    public function update(Request $request, $id, $date = null)
    {
        if (!Gate::allows('edit employee leaves')) {
            abort(403);
        }

        $request->validate([
            'employee_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'type' => 'required',
            'reason' => 'required',
        ]);

        if ($date) {
            // Update grouped leaves
            $leaves = EmployeeLeave::where('employee_id', $id)
                ->whereDate('created_at', $date)
                ->get();

            if ($leaves->isEmpty()) {
                return back()->with('error', 'No leave records found.');
            }

            // Delete existing leaves for this group
            EmployeeLeave::where('employee_id', $id)
                ->whereDate('created_at', $date)
                ->delete();

            // Create new leaves with updated data
            $start_date = Carbon::parse($request->start_date);
            $end_date = $request->end_date ? Carbon::parse($request->end_date) : $start_date;
            $dates = $start_date->toPeriod($end_date, '1 day');

            foreach ($dates as $date) {
                EmployeeLeave::create([
                    'employee_id' => $request->employee_id,
                    'date' => $date,
                    'type' => $request->type,
                    'reason' => $request->reason,
                    'description' => $request->description,
                    'actual_start_date' => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                    'actual_end_date' => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                    'status' => $leaves->first()->status, // Keep original status
                    'created_at' => $leaves->first()->created_at, // Keep original creation time
                ]);
            }

            return redirect()->route('employee-leaves.index')
                ->with('success', 'Employee leaves updated successfully.');
        } else {
            // Update single leave record
            $leave = EmployeeLeave::findOrFail($id);

            $leave->update([
                'employee_id' => $request->employee_id,
                'date' => $request->start_date,
                'type' => $request->type,
                'reason' => $request->reason,
                'description' => $request->description,
                'actual_start_date' => $request->actual_start_date,
                'actual_end_date' => $request->actual_end_date,
            ]);

            return redirect()->route('employee-leaves.index')
                ->with('success', 'Employee leave updated successfully.');
        }
    }
}
