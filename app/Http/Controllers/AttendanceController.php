<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Punch;
use App\Models\PublicHoliday;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\FortnightDates;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view attendance')) {
            abort(403);
        }
        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if (!$fortnight) {
            $fortnight = null;
        }
        $dateRange = $request->input('date_range');
        $attendances = Punch::with('user')->latest();

        if ($dateRange) {
            list($startDate, $endDate) = explode(' to ', $dateRange);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $attendances = $attendances->whereBetween('in_time', [$startDate, $endDate]);
        } else {
            $startDate = Carbon::parse($fortnight->start_date)->startOfDay();
            $endDate = Carbon::parse($fortnight->end_date)->endOfDay();
            $attendances = $attendances->whereBetween('in_time', [$startDate, $endDate]);
        }

        $attendances = $attendances->get();
        foreach ($attendances as $key => $attendance) {
            $inTime = Carbon::parse($attendance->in_time);
            $outTime = Carbon::parse($attendance->out_time);
            if ($inTime < $outTime) {
                // $workedHours = $inTime->diffInHours($outTime);
                //$loggedHours = $workedHours;
                $diff = $inTime->diff($outTime);

                $workedHours = $diff->h; // Hours part
                $workedMinutes = $diff->i; // Minutes part

                $loggedHours = $workedHours . '.' . $workedMinutes;
            } else {
                $loggedHours = 'N/A';  // If no valid out time or invalid time order
            }
            $attendance['total_hours'] = $loggedHours;
        }
        logActivity('View Attendance', 'Viewed Attendance details');

        return view('admin.attendance.index', compact('attendances', 'fortnight'));
    }
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y H:i:s', $date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function edit($id)
    {
        if (!Gate::allows('edit attendance')) {
            abort(403);
        }
        $attendance = Punch::with('user')->where('id', $id)->first();
        $in_location = json_decode($attendance->in_location);
        $attendance['in_location'] = $in_location->formatted_address ?? '';
        $out_location = json_decode($attendance->out_location);
        $attendance['out_location'] = $out_location->formatted_address ?? '';

        return view('admin.attendance.edit', compact('attendance'));
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit attendance')) {
            abort(403);
        }
        $request->validate([
            'punch_in' => 'required|date',
            'punch_out' => 'required|date|after:punch_in'
        ]);

        $attendance = Punch::where('id', $id)->first();
        $oldValues = $attendance->toArray();

        $attendance->update([
            'in_time'   => $this->parseDate($request->punch_in),
            'out_time'  => $this->parseDate($request->punch_out)
        ]);
        $newValues = $attendance->toArray();

        // 🔹 Log update activity
        logActivity(
            'Update Attendance',
            'Updated Attendance ID: ' . $attendance->id .
                ' | Changes: ' . json_encode(array_diff_assoc($newValues, $oldValues))
        );

        return redirect()->route('attendance.index')->with('success', 'Attendance updated successfully.');
    }

    public function destroy(string $id)
    {
        if (!Gate::allows('delete attendance')) {
            abort(403);
        }
        $attendance = Punch::where('id', $id)->first();

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
        // 🔹 Log delete activity
        logActivity(
            'Delete Attendance',
            'Deleted Attendance ID: ' . $id
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully.'
        ]);
    }

    public function exportAttendance(Request $request)
    {
        $today = Carbon::now();
        $fortnight = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        if (!$fortnight) {
            $fortnight = null;
        }

        $dateRange = $request->input('date_range');

        if ($dateRange) {
            $dateParts = explode(' to ', $dateRange);
            if (count($dateParts) === 2) {
                list($startDate, $endDate) = $dateParts;
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
            }
        } else {
            $startDate = Carbon::parse($fortnight->start_date)->startOfDay();
            $endDate = Carbon::parse($fortnight->end_date)->endOfDay();
        }

        $publicHolidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])->pluck('date')->toArray();

        logActivity('File Export', 'Exported Attendance CSV');
        return Excel::download(new AttendanceExport($startDate, $endDate, $publicHolidays), 'attendance-list.xlsx');
    }
}
