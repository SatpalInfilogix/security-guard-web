<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOvertime;
use App\Models\EmployeeOvertimeMain;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use App\Models\PublicHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeOvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('view employee overtime')) {
            abort(403);
        }
        $displayOvertimes = EmployeeOvertimeMain::with(['detail','employee'])->get();
        $displayOvertimes->map(function ($item) {
            $item->total_hours = $item->detail->sum('hours');
            return $item;
        });
      //  $overtimes = EmployeeOvertime::with('employee')->get();
        //dd($displayOvertimes);
       /* $groupedOvertimes = $overtimes->groupBy(function ($item) {
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
        })->values();*/

        return view('admin.employee-overtime.index', ['overtimes' => $displayOvertimes]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Gate::allows('create employee overtime')) {
            abort(403);
        }
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
        if (!Gate::allows('create employee overtime')) {
            abort(403);
        }
        $request->validate([
            'employee_id.*' => 'required|exists:users,id',
            'work_date.*'   => 'required|date',
            'actual_date.*' => 'nullable|date',
            'rate.*'       => 'required|numeric|min:0.01',
            'hours.*'      => 'required|numeric|min:0.01|max:24',
        ]);

        $employeeIds = $request->input('employee_id', []);
        $workDates   = $request->input('work_date', []);
        $rates       = $request->input('rate', []);
        $hours       = $request->input('hours', []);
        $actualDates = $request->input('actual_date', []);

        // Create main record using first item data
        $mainId = EmployeeOvertimeMain::create([
            'employee_id' => $employeeIds[0],
            'rate' => $rates[0],
            'work_date' => $workDates[0],
            'actual_date' => $actualDates[0] ?? null,
        ])->id;

        foreach ($employeeIds as $index => $empId) {
            $workDate = Carbon::parse($workDates[$index]);

            $isHoliday = PublicHoliday::whereDate('date', $workDate->toDateString())->exists();

            $multiplier = 1;
            if ($isHoliday || $workDate->isSunday()) {
                $multiplier = 2;
            } elseif ($workDate->isSaturday()) {
                $multiplier = 1.5;
            }

            $overtimeIncome = $rates[$index] * $hours[$index] * $multiplier;

            EmployeeOvertime::create([
                'employee_overtime_main_id' => $mainId,
                'employee_id'     => $empId,
                'work_date'       => $workDates[$index],
                'actual_date'     => $actualDates[$index] ?? null,
                'rate'            => $rates[$index],
                'hours'           => $hours[$index],
                'overtime_income' => $overtimeIncome,
            ]);
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
    public function edit($employee_id, $id)
    {
        if (!Gate::allows('edit employee overtime')) {
            abort(403);
        }
        $employees = User::role('employee')->get();
        $overtimes = EmployeeOvertimeMain::with(['detail','employee'])->where('employee_id',$employee_id)->where('id',$id)->first();
        /*$overtimes = EmployeeOvertime::where('employee_id', $employee_id)
            ->whereDate('created_at', $date)
            ->get();
        if ($overtimes->isEmpty()) {
            abort(404, 'No overtime records found for this employee and date.');
        }*/
        return view('admin.employee-overtime.edit', compact('overtimes', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $employee_id, $id)
    {
        if (!Gate::allows('edit employee overtime')) {
            abort(403);
        }
        $mainId = $id;
        $request->validate([
            'employee_id.*' => 'required|exists:users,id',
            'work_date.*'   => 'required|date',
            'actual_date.*' => 'nullable|date',
            'rate.*'        => 'required|numeric|min:0.01',
            'hours.*'       => 'required|numeric|min:0.01',
        ]);

        $ids         = $request->input('ids', []);
        $employeeIds = $request->input('employee_id', []);
        $dates       = $request->input('work_date', []);
        $actualDates = $request->input('actual_date', []);
        $rates       = $request->input('rate', []);
        $hours       = $request->input('hours', []);

        
        $submittedIds = array_filter($ids, fn($id) => !is_null($id) && $id !== '');
        $existingOvertimeIds = EmployeeOvertime::where('employee_id', $employee_id)
            ->where('employee_overtime_main_id', $mainId)
            ->pluck('id')
            ->toArray();

        $deletedIds = array_diff($existingOvertimeIds, $submittedIds);

        if (!empty($deletedIds)) {
            EmployeeOvertime::whereIn('id', $deletedIds)->delete();
        }

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
                'employee_overtime_main_id' => $mainId,
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
    public function destroy($employee_id, $id)
    {
        if (!Gate::allows('delete employee overtime')) {
            abort(403);
        }
        try {
            // Delete the main record and all associated overtime records
            EmployeeOvertimeMain::where('id', $id)
                ->where('employee_id', $employee_id)
                ->delete();

            EmployeeOvertime::where('employee_overtime_main_id', $id)
                ->where('employee_id', $employee_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee overtime records deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete overtime records: ' . $e->getMessage()
            ], 500);
        }
    }
}
