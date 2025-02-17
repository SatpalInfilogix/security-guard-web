<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeLeave;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmployeeLeavesController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view employee leaves')) {
            abort(403);
        }
        return view('admin.employee-leaves.index');
    }

    public function create()
    {
        if(!Gate::allows('create employee leaves')) {
            abort(403);
        }
        $userRole = Role::where('name', 'Employee')->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.employee-leaves.create', compact('employees'));
    }

    public function getEmployeeLeaves(Request $request)
    {
        $leaves = EmployeeLeave::with('user');

        if ($request->has('leave_status') && !empty($request->leave_status)) {
            $leaves->where('status', 'like', '%' . $request->leave_status . '%');
        }
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $leaves->where(function($query) use ($searchValue) {
                $query->where('date', 'like', '%' . $searchValue . '%')
                    ->orWhere('reason', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = EmployeeLeave::count();

        $filteredRecords = $leaves->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $leaves = $leaves->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $leaves,
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create employee leaves')) {
            abort(403);
        }
        $request->validate([
            'employee_id'   => 'required',
            'start_date'    => 'required|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
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
                    'reason'      => $request->reason,
                    'description' => $request->description,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('employee-leaves.index')->with('error', 'Employee Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('employee-leaves.index')->with('success', 'Employee Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }

    public function destroy($id)
    {
        if(!Gate::allows('delete employee leaves')) {
            abort(403);
        }

        EmployeeLeave::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee Leave deleted successfully.'
        ]);
    }

    public function updateLeaveStatus($leaveId, Request $request)
    {
        $request->validate([
            'status' => 'required',
        ]);

        $leave = EmployeeLeave::where('id', $leaveId)->first();
        if (!$leave) {
            return response()->json(['success' => false, 'message' => 'Employee Leave record not found.'], 404);
        }

        if ($request->status === 'Rejected' && empty($request->rejection_reason)) {
            return response()->json(['success' => false, 'message' => 'Rejection reason is required.'], 400);
        }

        $leave->status = $request->status;
        if ($request->status === 'Rejected') {
            $leave->rejection_reason = $request->rejection_reason;
        }
        $leave->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

}
