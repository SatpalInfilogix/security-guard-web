<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RateMaster;
class RateMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rateMasters = RateMaster::latest()->get();

        return view('admin.rate-master.index', compact('rateMasters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.rate-master.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'guard_type'        => 'required',
            'regular_rate'      => 'required',
            'laundry_allowance' => 'required',
        ]);

        $rateMaster = RateMaster::create([
            'guard_type'        => $request->guard_type,
            'regular_rate'      => $request->regular_rate,
            'laundry_allowance' => $request->laundry_allowance,
            'canine_premium'    => $request->canine_premium,
            'fire_arm_premium'  => $request->fire_arm_premium,
            'gross_hourly_rate' => $request->gross_hourly_rate,
            'overtime_rate'     => $request->overtime_rate,
            'holiday_rate'      => $request->holiday_rate
        ]);

        return redirect()->route('rate-master.index')->with('success', 'Rate Master created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rateMaster = RateMaster::where('id', $id)->first();

        return view('admin.rate-master.edit', compact('rateMaster'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'guard_type'        => 'required',
            'regular_rate'      => 'required',
            'laundry_allowance' => 'required',
        ]);

        $rateMaster = RateMaster::where('id', $id)->update([
            'guard_type'        => $request->guard_type,
            'regular_rate'      => $request->regular_rate,
            'laundry_allowance' => $request->laundry_allowance,
            'canine_premium'    => $request->canine_premium,
            'fire_arm_premium'  => $request->fire_arm_premium,
            'gross_hourly_rate' => $request->gross_hourly_rate,
            'overtime_rate'     => $request->overtime_rate,
            'holiday_rate'      => $request->holiday_rate
        ]);

        return redirect()->route('rate-master.index')->with('success', 'Rate Master updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rateMaster = RateMaster::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rate Master deleted successfully.'
        ]);
    }
}
