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
            'type'    => 'required',
            'rate'     => 'required',
        ]);

        $rateMaster = RateMaster::create([
            'type' => $request->type,
            'rate'  => $request->rate,
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
            'type'    => 'required',
            'rate'     => 'required',
        ]);

        $rateMaster = RateMaster::where('id', $id)->update([
            'type' => $request->type,
            'rate'  => $request->rate
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
