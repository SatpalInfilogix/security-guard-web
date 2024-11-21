<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
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
