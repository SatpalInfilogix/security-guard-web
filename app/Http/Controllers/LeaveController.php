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
            'status' => 'required',
            'created_date' => 'required|date',
        ]);

        $createdDate = $request->created_date;

        // Fetch leaves for this guard and created date
        $leaves = Leave::where('guard_id', $guardId)
            ->whereDate('created_at', $createdDate)
            ->get();

        if ($leaves->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Leave records not found for this user and date.'
            ], 404);
        }

        if ($request->status === 'Rejected' && empty($request->rejection_reason)) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection reason is required.'
            ], 400);
        }

        // Update all related leave entries
        foreach ($leaves as $leave) {
            $leave->status = $request->status;
            if ($request->status === 'Rejected') {
                $leave->rejection_reason = $request->rejection_reason;
            }
            $leave->save();
        }

        // $title = 'Leave Status Update';
        // $body = "Your leave status has been updated: {$request->status}";
        // $this->pushNotificationService->sendNotification($guardId, $title, $body);

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
            DB::raw('DATE(created_at) as created_date'),
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description'),
        )
            ->groupBy('guard_id', DB::raw('DATE(created_at)'))
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

        foreach ($dates as $date) {
            $existingLeave = Leave::where('guard_id', $request->guard_id)->whereDate('date', $date)->exists();

            if ($existingLeave) {
                $conflictingDates[] = $date->toDateString();
            } else {
                Leave::create([
                    'guard_id'    => $request->guard_id,
                    'date'        => $date,
                    'reason'      => $request->reason,
                    'leave_type'  => $request->leave_type,
                    'description' => $request->description,
                    'actual_start_date' => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                    'actual_end_date'   => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                ]);
                $createdDates[] = $date->toDateString();
            }
        }

        if (!empty($conflictingDates)) {
            return redirect()->route('leaves.index')->with('error', 'Leave already applied for the following dates: ' . implode(', ', $conflictingDates));
        }

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully for the following dates: ' . implode(', ', $createdDates));
    }

    public function edit($guardId, $date)
    {
        $leave = Leave::select(
            'guard_id',
            DB::raw('DATE(created_at) as created_date'),
            DB::raw('MIN(date) as start_date'),
            DB::raw('MAX(date) as end_date'),
            DB::raw('MIN(status) as status'),
            DB::raw('MIN(reason) as reason'),
            DB::raw('MIN(leave_type) as leave_type'),
            DB::raw('MIN(actual_start_date) as actual_start_date'),
            DB::raw('MIN(actual_end_date) as actual_end_date'),
            DB::raw('MIN(description) as description'),
        )
            ->where('guard_id', $guardId)
            ->whereDate('created_at', $date)
            ->groupBy('guard_id', DB::raw('DATE(created_at)'))
            ->with('user')
            ->first();

        if (!$leave) {
            abort(404, 'Leave record not found for given user and date.');
        }
        $userRole = Role::where('id', 3)->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.leaves.edit', compact('leave', 'securityGuards'));
    }

    public function update(Request $request, $guardId, $createdDate)
    {
        if (!Gate::allows('edit leaves')) {
            abort(403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'leave_type' => 'required|string|in:Sick Leave,Vacation Leave,Maternity Leave',
            'actual_start_date'  => 'nullable|date',
            'actual_end_date'    => 'nullable|date|after_or_equal:actual_start_date',
            'reason' => 'required|string'
        ]);

        $start_date = Carbon::parse($request->start_date);
        $end_date = $request->end_date ? Carbon::parse($request->end_date) : $start_date;

        if ($end_date->lt($start_date)) {
            return redirect()->route('leaves.index')->with('error', 'End date cannot be earlier than start date.');
        }

        // Step 1: Get original created_at timestamp
        $originalCreatedAt = Leave::where('guard_id', $guardId)
            ->whereDate('created_at', $createdDate)
            ->orderBy('created_at')
            ->value('created_at');

        if (!$originalCreatedAt) {
            return redirect()->route('leaves.index')->with('error', 'Original leave record not found.');
        }

        // Step 2: Delete existing leaves in this group
        Leave::where('guard_id', $guardId)
            ->whereDate('created_at', $createdDate)
            ->delete();

        // Step 3: Re-create leave records with the original created_at
        $dates = Carbon::parse($start_date)->toPeriod($end_date, '1 day');
        $createdDates = [];

        foreach ($dates as $date) {
            Leave::create([
                'guard_id'    => $guardId,
                'date'        => $date,
                'reason'      => $request->reason,
                'leave_type'  => $request->leave_type,
                'description' => $request->description,
                'actual_start_date' => $request->actual_start_date ? Carbon::parse($request->actual_start_date) : null,
                'actual_end_date'   => $request->actual_end_date ? Carbon::parse($request->actual_end_date) : null,
                'created_at'  => $originalCreatedAt,
            ]);
            $createdDates[] = $date->toDateString();
        }

        return redirect()->route('leaves.index')->with('success', 'Leave updated successfully');
    }


    public function destroy($guardId, $date = null)
    {
        if (!Gate::allows('delete leaves')) {
            abort(403);
        }

        if (!$date) {
            return response()->json([
                'success' => false,
                'message' => 'Date is required for deletion.'
            ], 400);
        }
        Leave::where('guard_id', $guardId)
            ->whereDate('created_at', $date)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave deleted successfully.'
        ]);
    }
}
