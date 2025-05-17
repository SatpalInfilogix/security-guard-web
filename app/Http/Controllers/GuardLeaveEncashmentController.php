<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Leave;
use App\Models\GuardLeaveEncashment;
use Illuminate\Http\Request;

class GuardLeaveEncashmentController extends Controller
{
    public function index()
    {
        $encashments = GuardLeaveEncashment::with('guardUser')->latest()->get();
        return view('admin.guard-leave-encashment.index', compact('encashments'));
    }

    public function create()
    {
        $userRole = Role::find(3);

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.guard-leave-encashment.create', compact('guards'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
        ]);

        $guard = User::findOrFail($request->guard_id);

        $usedLeaves = Leave::where('guard_id', $guard->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->count();

        $pendingLeaves = max(0, 10 - $usedLeaves); // Assume 10 leave quota

        if ($request->encash_leaves > $pendingLeaves) {
            return redirect()->back()->withInput()->withErrors([
                'encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $pendingLeaves . ').'
            ]);
        }

        GuardLeaveEncashment::create([
            'guard_id' => $guard->id,
            'pending_leaves' => $pendingLeaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('guard-leave-encashment.index')->with('success', 'Leave Encashment recorded.');
    }

    public function edit($id)
    {
        $encashment = GuardLeaveEncashment::with('guardUser')->findOrFail($id);

        $userRole = Role::find(3); // or Role::where('name', 'Security Guard')->first()

        $guards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.guard-leave-encashment.edit', compact('encashment', 'guards'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'guard_id' => 'required|exists:users,id',
            'encash_leaves' => 'required|integer|min:1',
        ]);

        $encashment = GuardLeaveEncashment::findOrFail($id);
        $guard = User::findOrFail($request->guard_id);

        $usedLeaves = Leave::where('guard_id', $guard->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->count();

        $pendingLeaves = max(0, 10 - $usedLeaves);

        if ($request->encash_leaves > $pendingLeaves) {
            return redirect()->back()
                ->withErrors(['encash_leaves' => 'Encash leaves cannot be greater than pending leaves (' . $pendingLeaves . ').'])
                ->withInput();
        }

        $encashment->update([
            'guard_id' => $guard->id,
            'pending_leaves' => $pendingLeaves,
            'encash_leaves' => $request->encash_leaves,
        ]);

        return redirect()->route('guard-leave-encashment.index')->with('success', 'Leave Encashment updated.');
    }

    public function destroy($id)
    {
        GuardLeaveEncashment::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guard leave encashment record deleted successfully!'
        ]);
    }

    public function getPendingLeaves(Request $request)
    {
        $guardId = $request->guard_id;

        if (!$guardId) {
            return response()->json(['pending_leaves' => 0]);
        }

        $userRole = Role::find(3);

        $guard = User::where('id', $guardId)
            ->where('status', 'Active')
            ->whereHas('roles', function ($query) use ($userRole) {
                $query->where('role_id', $userRole->id);
            })->first();

        if (!$guard) {
            return response()->json(['pending_leaves' => 0]);
        }

        $usedLeaves = Leave::where('guard_id', $guard->id)
            ->where('status', 'approved')
            ->where('date', '>=', now()->subYear())
            ->count();

        $pendingLeaves = max(0, 10 - $usedLeaves);

        return response()->json(['pending_leaves' => $pendingLeaves]);
    }
}
