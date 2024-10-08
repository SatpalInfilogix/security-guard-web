<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PunchTable;
use Illuminate\Support\Facades\File;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendances = PunchTable::with('user')->latest()->get();

        return view('admin.attendance.index', compact('attendances'));
    }

    public function edit($id)
    {
        $attendance = PunchTable::with('user')->where('id', $id)->first();
        
        return view('admin.attendance.edit', compact('attendance'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'punch_in'    => 'required',
            'punch_out'   => 'required'
        ]);

        $attendance = PunchTable::where('id', $id)->first();
        $attendance->update([
            'in_time'   => $request->punch_in,
            'out_time'  => $request->punch_out
        ]);

        return redirect()->route('attendance.index')->with('success', 'Attendance updated successfully.');
    }

    public function destroy(string $id)
    {
        $attendance = PunchTable::where('id', $id)->first();

        $images = [
            public_path($attendance->in_image),
            public_path($attendance->out_image)
        ];
    
        foreach ($images as $image) {
            if (File::exists($image)) {
                File::delete($image);
            }
        }
    
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully.'
        ]);
    }
}
