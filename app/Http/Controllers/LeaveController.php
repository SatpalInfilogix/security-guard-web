<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PushNotificationService;
use Illuminate\Support\Str;

class LeaveController extends Controller
{
    protected $pushNotificationService;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    // protected $messaging;

    // public function __construct()
    // {
    //     $credentialsPath = public_path('assets/service-account.json');
    //     if (!file_exists($credentialsPath)) {
    //         throw new \Exception("Firebase credentials file does not exist at path: $credentialsPath");
    //     }

    //     $firebase = (new Factory)->withServiceAccount($credentialsPath);

    //     $this->messaging = $firebase->createMessaging();

    // }

    // public function sendLeaveNotification($deviceToken, $leaveStatus)
    // {
    //     $message = CloudMessage::withTarget('token', $deviceToken)
    //         ->withNotification([
    //             'title' => 'Leave Status Update',
    //             'body' => "Your leave status has been updated: $leaveStatus"
    //         ])

    //         ->withData([
    //             'leave_status' => $leaveStatus
    //         ]);

    //        $response =  $this->messaging->send($message);
    //        dd($response);
    //         Log::info("Your leave status has been updated:" . json_encode($response));
    //         return response()->json(['message' => 'Notification sent successfully!']);
    // }

    public function updateStatus($guardId, Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'batch_id' => 'required|uuid', // expect valid UUID
        ]);

        $batchId = $request->batch_id;

        // Fetch all leave records in the batch for this guard
        $leaves = Leave::where('guard_id', $guardId)
            ->where('batch_id', $batchId)
            ->get();

        if ($leaves->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Leave records not found for this guard and batch ID.'
            ], 404);
        }

        if ($request->status === 'Rejected' && empty($request->rejection_reason)) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required.'
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
            'message' => 'Leave status updated successfully.',
            'status' => $request->status,
            'guardId' => $guardId,
        ]);
    }

    public function sendNotificationAfterLeave($guardId = null, $status = null)
    {
        $title = 'Leave Status Update';
        $body = "Your leave status has been updated: {$status}";
        $this->pushNotificationService->sendNotification($guardId, $title, $body);
        return response()->json([
            'success' => true,
            'message' => 'Notification Sent!'
        ]);
    }

    public function index()
    {
        if (!Gate::allows('view leaves')) {
            abort(403);
        }
        $leaves = Leave::with('user')->latest()->get();

        return view('admin.leaves.index', compact('leaves'));
    }

    public function getLeave(Request $request)
    {
        $query = Leave::select(
            'guard_id',
            'batch_id',
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description')
        )
        ->groupBy('guard_id', 'batch_id')
        ->with('user');

        if ($request->has('leave_status') && !empty($request->leave_status)) {
            $query->where('status', $request->leave_status);
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
                    ->orWhere('reason', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q2) use ($searchValue) {
                        $q2->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $countQuery = (clone $query);
        $totalRecords = $countQuery->get()->count();

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

    public function create()
    {
        if (!Gate::allows('create leaves')) {
            abort(403);
        }
        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.leaves.create', compact('securityGuards'));
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create leaves')) {
            abort(403);
        }

        $request->validate([
            'guard_id'       => 'required',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'leave_type' => 'required|string|in:Sick Leave,Vacation Leave,Maternity Leave',
            'actual_start_date'  => 'nullable|date',
            'actual_end_date'    => 'nullable|date|after_or_equal:actual_start_date',
        ]);

        $start_date = Carbon::parse($request->start_date);
        $end_date = $request->end_date ? Carbon::parse($request->end_date) : $start_date;

        if ($end_date->lt($start_date)) {
            return redirect()->route('leaves.index')->with('error', 'End date cannot be earlier than start date.');
        }

        $dates = Carbon::parse($start_date)->toPeriod($end_date, '1 day');
        $conflictingDates = [];
        $createdDates = [];

        // Generate a unique batch ID for this leave request
        $batchId = Str::uuid();

        foreach ($dates as $date) {
            $existingLeave = Leave::where('guard_id', $request->guard_id)->whereDate('date', $date)->exists();

            if ($existingLeave) {
                $conflictingDates[] = $date->toDateString();
            } else {
                Leave::create([
                    'guard_id'          => $request->guard_id,
                    'date'              => $date,
                    'reason'            => $request->reason,
                    'leave_type'        => $request->leave_type,
                    'description'       => $request->description,
                    'actual_start_date' => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                    'actual_end_date'   => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                    'batch_id'          => $batchId,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('leaves.index')->with('error', 'Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }

    public function edit($guardId, $batchId)
    {
        // Fetch all leaves for the given guard and batch
        $leaves = Leave::where('guard_id', $guardId)
            ->where('batch_id', $batchId)
            ->with('user')
            ->orderBy('date')
            ->get();

        if ($leaves->isEmpty()) {
            abort(404, 'Leave records not found for the specified guard and batch.');
        }

        // Build a pseudo leave object similar to getLeave()
        $leave = new \stdClass();
        $leave->guard_id = $guardId;
        $leave->batch_id = $batchId;
        $leave->start_date = $leaves->min('date');
        $leave->end_date = $leaves->max('date');
        $leave->status = $leaves->min('status');
        $leave->reason = $leaves->min('reason');
        $leave->actual_start_date = $leaves->min('actual_start_date');
        $leave->actual_end_date = $leaves->min('actual_end_date');
        $leave->description = $leaves->min('description');
        $leave->leave_type = $leaves->min('leave_type');
        $leave->user = $leaves->first()->user; // Attach user for dropdowns etc.

        // Dates for the leave period (if needed for frontend logic)
        $leaveDates = $leaves->pluck('date')->toArray();

        // Get security guards for dropdown
        $userRole = Role::find(3);
        $securityGuards = User::with('guardAdditionalInformation')
            ->whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })
            ->where('status', 'Active')
            ->latest()
            ->get();
        // dd($leave);
        return view('admin.leaves.edit', [
            'leave' => $leave,
            'leaveDates' => $leaveDates,
            'securityGuards' => $securityGuards
        ]);
    }

    public function update(Request $request, $guardId, $batchId)
    {
        if (!Gate::allows('edit leaves')) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'leave_type' => 'required|string|in:Sick Leave,Vacation Leave,Maternity Leave',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'reason' => 'required|string',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : $startDate;

            if ($endDate->lt($startDate)) {
                return back()->with('error', 'End date cannot be earlier than start date.');
            }

            // Fetch the original leave group
            $originalLeaves = Leave::where('guard_id', $guardId)
                ->where('batch_id', $batchId)
                ->get();

            if ($originalLeaves->isEmpty()) {
                return back()->with('error', 'Original leave records not found.');
            }

            $originalCreatedAt = $originalLeaves->first()->created_at;

            // Delete existing records in this batch
            Leave::where('guard_id', $guardId)
                ->where('batch_id', $batchId)
                ->delete();

            // Recreate leave entries with the same batch ID
            $period = Carbon::parse($startDate)->toPeriod($endDate);

            foreach ($period as $date) {
                Leave::create([
                    'guard_id'          => $guardId,
                    'date'              => $date->format('Y-m-d'),
                    'reason'            => $validated['reason'],
                    'leave_type'        => $validated['leave_type'],
                    'description'       => $validated['description'],
                    'actual_start_date' => $validated['actual_start_date'] ? Carbon::parse($validated['actual_start_date']) : null,
                    'actual_end_date'   => $validated['actual_end_date'] ? Carbon::parse($validated['actual_end_date']) : null,
                    'batch_id'          => $batchId,
                    'created_at'        => $originalCreatedAt,
                    'updated_at'        => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('leaves.index')
                ->with('success', 'Leave updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating leave: ' . $e->getMessage());
        }
    }

    public function destroy($guardId, $batchId = null)
    {
        if (!Gate::allows('delete leaves')) {
            abort(403);
        }

        if (!$batchId) {
            return response()->json([
                'success' => false,
                'message' => 'Batch ID is required for deletion.'
            ], 400);
        }

        $deleted = Leave::where('guard_id', $guardId)
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
            'message' => 'Leave deleted successfully.'
        ]);
    }
}
