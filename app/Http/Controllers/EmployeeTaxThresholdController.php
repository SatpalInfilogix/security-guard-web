<?php

namespace App\Http\Controllers;

use App\Models\EmployeeTaxThreshold;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeTaxThresholdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $thresholds = EmployeeTaxThreshold::get();
        return view('admin.employee-tax-threshold.index', compact('thresholds'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.employee-tax-threshold.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'annual' => 'required',
            'monthly' => 'required',
            'fortnightly' => 'required',
            'effective_date' => 'required',
        ]);

        EmployeeTaxThreshold::create([
            'annual' => $request->annual,
            'monthly' => $request->monthly,
            'fortnightly' => $request->fortnightly,
            'effective_date' => $request->effective_date,
        ]);

        return redirect()->route('employee-tax-threshold.index')->with('success', 'Tax threshold saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $threshold = EmployeeTaxThreshold::findOrFail($id);
        return view('admin.employee-tax-threshold.show', compact('threshold'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $threshold = EmployeeTaxThreshold::findOrFail($id);
        return view('admin.employee-tax-threshold.edit', compact('threshold'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'annual' => 'required',
            'monthly' => 'required',
            'fortnightly' => 'required',
            'effective_date' => 'required',
        ]);

        $threshold = EmployeeTaxThreshold::findOrFail($id);

        $threshold->update([
            'annual' => $request->annual,
            'monthly' => $request->monthly,
            'fortnightly' => $request->fortnightly,
            'effective_date' => $request->effective_date,
        ]);

        return redirect()->route('employee-tax-threshold.index')->with('success', 'Tax threshold updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Ensure there's at least one record left
        if (EmployeeTaxThreshold::count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one tax threshold must be present. Deletion aborted to prevent payroll issues.'
            ], 422);
        }

        $threshold = EmployeeTaxThreshold::findOrFail($id);
        $threshold->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax threshold deleted successfully.',
        ]);
    }
}
