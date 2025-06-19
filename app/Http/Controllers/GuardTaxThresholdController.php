<?php

namespace App\Http\Controllers;

use App\Models\GuardTaxThreshold;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GuardTaxThresholdController extends Controller
{
    public function index()
    {
        $thresholds = GuardTaxThreshold::latest()->get();
        return view('admin.guard-tax-threshold.index', compact('thresholds'));
    }

    public function create()
    {
        return view('admin.guard-tax-threshold.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'annual' => 'required|numeric|min:0',
            'monthly' => 'nullable|numeric|min:0',
            'fortnightly' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        GuardTaxThreshold::create([
            'annual' => $validated['annual'],
            'monthly' => $validated['monthly'],
            'fortnightly' => $validated['fortnightly'],
            'effective_date' => Carbon::parse($validated['effective_date']),
        ]);

        return redirect()->route('guard-tax-threshold.index')->with('success', 'Guard Tax Threshold created successfully.');
    }

     public function edit(string $id)
    {
        $threshold = GuardTaxThreshold::findOrFail($id);
        return view('admin.guard-tax-threshold.edit', compact('threshold'));
    }

    public function update(Request $request, GuardTaxThreshold $guardTaxThreshold)
    {
        $validated = $request->validate([
            'annual' => 'required|numeric|min:0',
            'monthly' => 'nullable|numeric|min:0',
            'fortnightly' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
        ]);

        $guardTaxThreshold->update([
            'annual' => $validated['annual'],
            'monthly' => $validated['monthly'],
            'fortnightly' => $validated['fortnightly'],
            'effective_date' => Carbon::parse($validated['effective_date']),
        ]);

        return redirect()->route('guard-tax-threshold.index')->with('success', 'Guard Tax Threshold updated successfully.');
    }

    public function destroy(string $id)
    {
        $threshold = GuardTaxThreshold::findOrFail($id);
        $threshold->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax threshold deleted successfully.',
        ]);
    }
}
