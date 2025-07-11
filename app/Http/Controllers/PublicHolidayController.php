<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PublicHoliday;
use Illuminate\Support\Facades\Gate;

class PublicHolidayController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view public holiday')) {
            abort(403);
        }
        $publicHolidays = PublicHoliday::latest()->get();

        return view('admin.public-holidays.index', [
            'publicHolidays' => $publicHolidays,
            'page' => $request->input('page', 1),
            'year' => $request->input('year')
        ]);
    }

    public function create()
    {
        if (!Gate::allows('create public holiday')) {
            abort(403);
        }
        return view('admin.public-holidays.create');
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create public holiday')) {
            abort(403);
        }
        $request->validate([
            'holiday_name'  => 'required',
            'date'          => 'required',
        ]);

        $rateMaster = PublicHoliday::create([
            'holiday_name'  => $request->holiday_name,
            'date'          => $request->date,
        ]);

        return redirect()->route('public-holidays.index')->with('success', 'Public Holiday created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit($id)
    {
        if (!Gate::allows('edit public holiday')) {
            abort(403);
        }
        $publicHoliday = PublicHoliday::where('id', $id)->first();

        return view('admin.public-holidays.edit', compact('publicHoliday'));
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit public holiday')) {
            abort(403);
        }
        $request->validate([
            'holiday_name'  => 'required',
            'date'          => 'required',
        ]);

        $publicHoliday = PublicHoliday::where('id', $id)->first();

        $publicHoliday->update([
            'holiday_name'  => $request->holiday_name,
            'date'          => $request->date
        ]);

        return redirect()->route('public-holidays.index')->with('success', 'Public Holiday updated successfully.');
    }

    public function destroy(string $id)
    {
        if (!Gate::allows('delete public holiday')) {
            abort(403);
        }
        $user = PublicHoliday::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Public Holiday deleted successfully.'
        ]);
    }
}
