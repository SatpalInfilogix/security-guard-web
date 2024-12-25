<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use APp\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class LeaveController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view leaves')) {
            abort(403);
        }
        $leaves = Leave::with('user')->latest()->get();

        return view('admin.leaves.index', compact('leaves'));
    }

    public function create()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.leaves.create', compact('securityGuards'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guard_id'       => 'required',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
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
            $existingLeave = Leave::where('guard_id', $request->guard_id)->whereDate('date', $date)->exists();

            if ($existingLeave) {
                $conflictingDates[] = $date->toDateString();
            } else {
                Leave::create([
                    'guard_id'    => $request->guard_id,
                    'date'        => $date,
                    'reason'      => $request->reason,
                    'description' => $request->description,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('leaves.index')->with('error', 'Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }

    public function edit(Leave $leave)
    {
        return view('admin.leaves.edit', compact('leave'));
    }

    public function updateStatus($leaveId, Request $request)
    {
        $request->validate([
            'status' => 'required',
        ]);
        
        $leave = Leave::where('id', $leaveId)->first();
        if (!$leave) {
            return response()->json(['success' => false, 'message' => 'Leave record not found.'], 404);
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

    public function destroy(Leave $leave)
    {
        if(!Gate::allows('delete leaves')) {
            abort(403);
        }
        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave deleted successfully.'
        ]);
    }
}
