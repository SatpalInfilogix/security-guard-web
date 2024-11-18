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
            'date'    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'  => false,
                'message'  => $validator->errors()->first()
            ]);
        }

        $leave = Leave::create([
            'guard_id'    => Auth::id(),
            'date'        => Carbon::parse($request->date)->format('Y-m-d'),
            'reason'      => $request->reason,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave created successfully.',
            'data'    => $leave
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
}
