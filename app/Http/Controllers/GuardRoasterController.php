<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuardRoaster;
use App\Models\User;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\PublicHoliday;
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
            'guard_id'       => 'required',
            'client_id'      => 'required',
            'client_site_id' => 'required',
            // 'date'           => 'required|date',
            // 'start_time'     => 'required|date_format:H:i',
            // 'end_time'       => 'required|date_format:H:i',
        ]);

        $guardRoaster = GuardRoaster::updateOrCreate(
            [
                'guard_id' => $request->guard_id,
                'date'     => $request->date,  // We use these two attributes to search for the existing record
            ],
            [
                'client_id'      => $request->client_id,
                'client_site_id' => $request->client_site_id,
                'start_time'     => $request->start_time,
                'end_time'       => $request->end_time
            ]
        );

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

        $existingGuardRoaster = GuardRoaster::where('guard_id', $request->guard_id)->where('date', $request->date)
                                            ->where('id', '!=', $guardRoaster->id)->first();

        if ($existingGuardRoaster) {
            return redirect()->back()->withErrors(['date' => 'Date already assigned to this guard.'])->withInput();
        }

        $guardRoaster->update([
            'guard_id'       => $request->guard_id,
            'client_id'      => $request->client_id,
            'client_site_id' => $request->client_site_id,
            'date'           => $request->date,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time
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

    public function getPublicHolidays()
    {
        $publicHolidays = PublicHoliday::latest()->get();
        return response()->json($publicHolidays);
    }

    public function destroy(GuardRoaster $guardRoaster)
    {
        $guardRoaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guard Roaster deleted successfully.'
        ]);
    }

    public function getGuardRoasterDetails(Request $request)
    {
        $guardId = $request->input('guard_id');
        $date = $request->input('date');

        if (!$guardId || !$date) {
            return response()->json(['error' => 'Date and Guard ID are required'], 400);
        }

        $guardRoaster = GuardRoaster::where('guard_id', $guardId)
                                    ->where('date', $date)
                                    ->first();

        if (!$guardRoaster) {
            return response()->json(['error' => 'No roaster found for this guard and date'], 404);
        }

        $client = $guardRoaster->client_id;
        $clientSites = ClientSite::where('client_id', $client)->get();

        return response()->json([
            'client_id' => $client,
            'client_site_id' => $guardRoaster->client_site_id,
            'client_sites' => $clientSites, // Pass all available client sites for the selected client
            'start_time' => $guardRoaster->start_time,
            'end_time' => $guardRoaster->end_time,
        ]);
    }
}