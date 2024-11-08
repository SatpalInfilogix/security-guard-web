<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuardRoaster;
use App\Models\User;
use App\Models\Client;
use App\Models\ClientSite;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class GuardRoasterController extends Controller
{
    public function index()
    {
        $guardRoasters = GuardRoaster::with('user', 'client')->latest()->get();

        return view('admin.guard-roaster.index', compact('guardRoasters'));
    }

    public function create()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->latest()->get();

        $clients = Client::latest()->get();

        return view('admin.guard-roaster.create', compact('securityGuards', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guard_id'    => 'required',
            'client_id'    => 'required',
            'client_site_id' => 'required'
        ]);

        GuardRoaster::create([
            'guard_id'       => $request->guard_id,
            'client_id'      => $request->client_id,
            'client_site_id' => $request->client_site_id,
            'date'           => $request->date,
            'start_time'     => $request->time
        ]);

        return redirect()->route('guard-roasters.index')->with('success', 'Guard Roaster created successfully.');
    }

    public function show($id) {
        //
    }

    public function edit(GuardRoaster $guardRoaster) 
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->latest()->get();

        $clients = Client::latest()->get();
        $clientSites = ClientSite::latest()->get();

        return view('admin.guard-roaster.edit', compact('securityGuards', 'clients', 'guardRoaster', 'clientSites'));
    }

    public function update(Request $request, GuardRoaster $guardRoaster)
    {
        $request->validate([
            'guard_id'    => 'required',
            'client_id'    => 'required',
            'client_site_id' => 'required'
        ]);

        $guardRoaster->update([
            'guard_id'       => $request->guard_id,
            'client_id'      => $request->client_id,
            'client_site_id' => $request->client_site_id,
            'date'           => $request->date,
            'start_time'     => $request->time
        ]);

        return redirect()->route('guard-roasters.index')->with('success', 'Guard Roaster updated successfully.');
    }

    public function getClientSites($clientId)
    {
        $clientSites = ClientSite::where('client_id', $clientId)->get();

        return response()->json($clientSites);
    }

    public function getAssignedDate($guardId)
    {
        $assignedDates = GuardRoaster::where('guard_id', $guardId)->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return response()->json($assignedDates);
    }
}
