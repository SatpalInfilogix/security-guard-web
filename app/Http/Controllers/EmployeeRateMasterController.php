<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeRateMaster;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class EmployeeRateMasterController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view employee rate master')) {
            abort(403);
        }
        $employeeRateMasters = EmployeeRateMaster::with('user')->get();

        return view('admin.employee-rate-master.index', compact('employeeRateMasters'));
    }

    public function create()
    {
        if(!Gate::allows('create employee rate master')) {
            abort(403);
        }
        $userRole = Role::where('id', 9)->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.employee-rate-master.create', compact('employees'));
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create employee rate master')) {
            abort(403);
        }
        $request->validate([
            'employee_id' => 'required|unique:employee_rate_masters,employee_id',
            'gross_salary' => 'required',
        ]);

        EmployeeRateMaster::create([
            'employee_id'    => $request->employee_id,
            'gross_salary'   => $request->gross_salary,
            'monthly_income' => $request->monthly_income,
        ]);

        return redirect()->route('employee-rate-master.index')->with('success', 'Employee Rate Master created successfully.');
    }

    public function edit($id)
    {
        if(!Gate::allows('edit employee rate master')) {
            abort(403);
        }
        $userRole = Role::where('id', 9)->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        $rateMaster = EmployeeRateMaster::with('user')->where('id', $id)->first();

        return view('admin.employee-rate-master.edit', compact('rateMaster', 'employees'));
    }

    public function update(Request $request, $id)
    {
        if(!Gate::allows('edit employee rate master')) {
            abort(403);
        }
        $request->validate([
            'employee_id' => 'required|unique:employee_rate_masters,employee_id,' . $id,
            'gross_salary' => 'required',
        ]);

        EmployeeRateMaster::where('id', $id)->update([
            'employee_id'    => $request->employee_id,
            'gross_salary'   => $request->gross_salary,
            'monthly_income' => $request->monthly_income,

        ]);

        return redirect()->route('employee-rate-master.index')->with('success', 'Employee Rate Master updated successfully.');
    }

    public function destroy(string $id)
    {
        if(!Gate::allows('delete employee rate master')) {
            abort(403);
        }
        EmployeeRateMaster::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee Rate Master deleted successfully.'
        ]);
    }
}
