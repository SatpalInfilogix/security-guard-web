<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
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

        $title ='Leave Status Update';
        $body = "Your leave status has been updated: $request->status";
        $this->pushNotificationService->sendNotification($leave->guard_id, $title, $body); 
        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function index()
    {
        if(!Gate::allows('view leaves')) {
            abort(403);
        }
        $leaves = Leave::with('user')->latest()->get();

        return view('admin.leaves.index', compact('leaves'));
    }

    public function getLeave(Request $request)
    {
        $leaves = Leave::with('user');

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

        $totalRecords = Leave::count();

        $filteredRecords = $leaves->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $leaves = $leaves->skip($start)->take($length)->get();

        // Returning permissions with the response
        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $leaves,
        ];

        return response()->json($data);
    }

    public function create()
    {
        if(!Gate::allows('create leaves')) {
            abort(403);
        }
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.leaves.create', compact('securityGuards'));
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create leaves')) {
            abort(403);
        }
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

    public function destroy($id)
    {
        if(!Gate::allows('delete leaves')) {
            abort(403);
        }
        Leave::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave deleted successfully.'
        ]);
    }
}
