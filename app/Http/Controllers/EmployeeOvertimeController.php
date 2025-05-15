<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOvertime;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
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
            return (object)[
                'employee'     => $group->first()->employee,
                'employee_id'  => $group->first()->employee_id,
                'created_date' => $group->first()->created_at->format('Y-m-d'),
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
        $employees = User::role('employee')->get();
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

        $errors = [];

        for ($i = 0; $i < count($employeeIds); $i++) {
            $rowData = [
                'employee_id' => $employeeIds[$i],
                'work_date'   => $workDates[$i],
                'rate'        => $rates[$i],
                'hours'       => $hours[$i],
            ];

            $validator = Validator::make($rowData, [
                'employee_id' => 'required|exists:users,id',
                'work_date'   => 'required|date',
                'rate'        => 'required|numeric|min:0',
                'hours'       => 'required|numeric|min:0|max:24',
            ]);

            if ($validator->fails()) {
                $errors[$i] = $validator->errors()->all();
                continue;
            }

            EmployeeOvertime::create($rowData);
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
            'work_date.*' => 'required|date',
            'rate.*' => 'required|numeric|min:0.01',
            'hours.*' => 'required|numeric|min:0.01',
        ]);

        $ids = $request->input('ids', []);
        $employeeIds = $request->input('employee_id', []);
        $dates = $request->input('work_date', []);
        $rates = $request->input('rate', []);
        $hours = $request->input('hours', []);

        foreach ($employeeIds as $index => $empId) {
            $id = $ids[$index] ?? null;

            if (!empty($id)) {
                $overtime = EmployeeOvertime::find($id);
                if ($overtime) {
                    $overtime->update([
                        'employee_id' => $empId,
                        'work_date' => $dates[$index],
                        'rate' => $rates[$index],
                        'hours' => $hours[$index],
                    ]);
                }
            } else {
                EmployeeOvertime::create([
                    'employee_id' => $empId,
                    'work_date' => $dates[$index],
                    'rate' => $rates[$index],
                    'hours' => $hours[$index],
                ]);
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
