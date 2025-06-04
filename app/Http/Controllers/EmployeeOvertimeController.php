<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOvertime;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use App\Models\PublicHoliday;
use Illuminate\Http\Request;

class EmployeeOvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overtimes = EmployeeOvertime::with('employee')->get();

        $groupedOvertimes = $overtimes->groupBy(function ($item) {
            return $item->employee_id . '-' . $item->created_at->format('Y-m-d');
        });

        $displayOvertimes = $groupedOvertimes->map(function ($group) {
            $first = $group->first();
            return (object)[
                'employee'     => $first->employee,
                'employee_id'  => $first->employee_id,
                'created_date' => $first->created_at->format('Y-m-d'),
                'actual_date'  => $first->actual_date
                    ? Carbon::parse($first->actual_date)->format('Y-m-d')
                    : 'N/A',
                'total_hours'  => $group->sum('hours'),
                'rate'         => $group->avg('rate'),
            ];
        })->values();

        return view('admin.employee-overtime.index', ['overtimes' => $displayOvertimes]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = User::role('employee')
            ->leftJoin('employee_rate_masters', 'users.id', '=', 'employee_rate_masters.employee_id')
            ->select('users.id', 'users.first_name', 'users.surname', 'employee_rate_masters.hourly_income')
            ->get();

        return view('admin.employee-overtime.create', compact('employees'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $employeeIds = $request->input('employee_id');
        $workDates   = $request->input('work_date');
        $rates       = $request->input('rate');
        $hours       = $request->input('hours');
        $actualDates = $request->input('actual_date');
        $errors = [];

        for ($i = 0; $i < count($employeeIds); $i++) {
            $rowData = [
                'employee_id' => $employeeIds[$i],
                'work_date'   => $workDates[$i],
                'rate'        => $rates[$i],
                'hours'       => $hours[$i],
                'actual_date' => $actualDates[$i] ?? null,
            ];

            $validator = Validator::make($rowData, [
                'employee_id' => 'required|exists:users,id',
                'work_date'   => 'required|date',
                'actual_date' => 'nullable|date',
                'rate'        => 'required|numeric|min:0',
                'hours'       => 'required|numeric|min:0|max:24',
            ]);

            if ($validator->fails()) {
                $errors[$i] = $validator->errors()->all();
                continue;
            }

            $date = Carbon::parse($rowData['work_date']);
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday

            $isHoliday = PublicHoliday::whereDate('date', $date->toDateString())->exists();

            $multiplier = 1;
            if ($isHoliday || $dayOfWeek == 0) {
                $multiplier = 2;
            } elseif ($dayOfWeek == 6) {
                $multiplier = 1.5;
            }

            $overtimeIncome = $rowData['rate'] * $rowData['hours'] * $multiplier;

            EmployeeOvertime::create([
                'employee_id'     => $rowData['employee_id'],
                'work_date'       => $rowData['work_date'],
                'actual_date'     => $rowData['actual_date'],
                'rate'            => $rowData['rate'],
                'hours'           => $rowData['hours'],
                'overtime_income' => $overtimeIncome,
            ]);
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['validation_errors' => $errors]);
        }

        return redirect()->route('employee-overtime.index')->with('success', 'Employee overtime records saved successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeOvertime $employeeOvertime)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($employee_id, $date)
    {
        $employees = User::role('employee')->get();

        $overtimes = EmployeeOvertime::where('employee_id', $employee_id)
            ->whereDate('created_at', $date)
            ->get();

        if ($overtimes->isEmpty()) {
            abort(404, 'No overtime records found for this employee and date.');
        }

        return view('admin.employee-overtime.edit', compact('overtimes', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $employee_id, $date)
    {
        $request->validate([
            'employee_id.*' => 'required|exists:users,id',
            'work_date.*'   => 'required|date',
            'actual_date.*' => 'nullable|date',
            'rate.*'        => 'required|numeric|min:0.01',
            'hours.*'       => 'required|numeric|min:0.01',
        ]);

        $ids = $request->input('ids', []);
        $employeeIds = $request->input('employee_id', []);
        $dates = $request->input('work_date', []);
        $actualDates = $request->input('actual_date', []);
        $rates = $request->input('rate', []);
        $hours = $request->input('hours', []);

        foreach ($employeeIds as $index => $empId) {
            $id = $ids[$index] ?? null;
            $workDate = Carbon::parse($dates[$index]);

            $isHoliday = PublicHoliday::whereDate('date', $workDate->toDateString())->exists();

            $multiplier = 1;
            if ($isHoliday || $workDate->isSunday()) {
                $multiplier = 2;
            } elseif ($workDate->isSaturday()) {
                $multiplier = 1.5;
            }

            $overtimeIncome = $rates[$index] * $hours[$index] * $multiplier;

            $data = [
                'employee_id'     => $empId,
                'work_date'       => $dates[$index],
                'actual_date'     => $actualDates[$index] ?? null,
                'rate'            => $rates[$index],
                'hours'           => $hours[$index],
                'overtime_income' => $overtimeIncome,
            ];

            if (!empty($id)) {
                $overtime = EmployeeOvertime::find($id);
                if ($overtime) {
                    $overtime->update($data);
                }
            } else {
                EmployeeOvertime::create($data);
            }
        }

        return redirect()->route('employee-overtime.index')->with('success', 'Employee overtime records updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($employee_id)
    {
        EmployeeOvertime::where('employee_id', $employee_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee overtime record deleted successfully!'
        ]);
    }
}
