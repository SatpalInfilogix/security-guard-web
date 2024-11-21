<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function leave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $start_date = Carbon::parse($request->start_date);
        $end_date = $request->end_date ? Carbon::parse($request->end_date) : $start_date;

        if ($end_date->lt($start_date)) {
            return response()->json([
                'success' => false,
                'message' => 'End date cannot be earlier than start date.'
            ]);
        }

        $dates = Carbon::parse($start_date)->toPeriod($end_date, '1 day');

        $conflictingDates = [];
        $createdDates = [];

        foreach ($dates as $date) {
            $existingLeave = Leave::where('guard_id', Auth::id())->whereDate('date', $date)->exists();

            if ($existingLeave) {
                $conflictingDates[] = $date->toDateString();
            } else {
                Leave::create([
                    'guard_id'    => Auth::id(),
                    'date'        => $date,
                    'reason'      => $request->reason,
                    'description' => $request->description,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return response()->json([
                'success' => false,
                'message' => 'Leave already applied for the following dates: ' . implode(', ', $conflictingDates),
                'conflicting_dates' => $conflictingDates,
                'creating_dates'    => $createdDates
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave created successfully.',
            'data'    => $createdDates,
        ]);
    }

    public function getLeave(Request $request)
    {
        $query = Leave::where('guard_id', Auth::id());

        if ($request->start_date && $request->end_date) {
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
    
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($request->start_date) {
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $query->where('date', '>=', $startDate);
        }

        $leaves = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Leave listing.',
            'data'    => $leaves
        ]);
    }

    public function cancelLeave($id, Request $request)
    {
        $leave = Leave::where('id', $id)->first();
        if (!$leave) {
            return response()->json(['message' => 'Leave not found.'], 404);
        }

        $leave->status = 'Cancelled';
        $leave->save();
      
        return response()->json([
            'success'   => true,
            'message'   => 'Leave cancelled successfully.',
            'leave'     => $leave
        ]);
    }
}
