<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDeduction;
use App\Models\EmployeeDeductionDetail;
use App\Models\TwentyTwoDayInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use App\Exports\EmployeeDeductionExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class EmployeeDeductionController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view employee deduction')) {
            abort(403);
        }
        return view('admin.employee-deductions.index');
    }
    public function getDeductionsData(Request $request)
    {
        $deductions = EmployeeDeduction::with('user');

        if ($request->has('search_name') && !empty($request->search_name)) {
            $deductions->whereHas('user', function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->search_name . '%')
                    ->orWhere('surname', 'like', '%' . $request->search_name . '%');
            });
        }

        if ($request->has('search_type') && !empty($request->search_type)) {
            $deductions->where('type', 'like', '%' . $request->search_type . '%');
        }

        if ($request->has('search_document_date') && !empty($request->search_document_date)) {
            $deductions->whereDate('document_date', Carbon::parse($request->search_document_date)->format('Y-m-d'));
        }

        if ($request->has('search_period_date') && !empty($request->search_period_date)) {
            $deductions->whereDate('start_date', '<=', Carbon::parse($request->search_period_date)->format('Y-m-d'))
                ->whereDate('end_date', '>=', Carbon::parse($request->search_period_date)->format('Y-m-d'));
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $deductions->where(function ($query) use ($searchValue) {
                $query->where('type', 'like', '%' . $searchValue . '%')
                    ->orWhere('amount', 'like', '%' . $searchValue . '%')
                    ->orWhere('no_of_payroll', 'like', '%' . $searchValue . '%')
                    ->orWhere('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($subQuery) use ($searchValue) {
                        $subQuery->where('user_code', 'like', '%' . $searchValue . '%')
                            ->orWhere('first_name', 'like', '%' . $searchValue . '%')
                            ->orWhere('surname', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = EmployeeDeduction::count();

        $filteredRecords = $deductions->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $deductions = $deductions->skip($start)->take($length)->get()->map(function ($item) {
            $item->user->full_name = $item->user->first_name . ' ' . $item->user->surname;
            $item->formatted_amount = formatAmount($item->amount);
            return $item;
        });

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $deductions,
        ];

        return response()->json($data);
    }

    public function create()
    {
        if (!Gate::allows('create employee deduction')) {
            abort(403);
        }
        $userRole = Role::where('id', 9)->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.employee-deductions.create', compact('employees'));
    }

    public function getEndDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $noOfPayrolls = $request->no_of_payroll ?? 1;
        $fortnightStart = TwentyTwoDayInterval::where('start_date', '<=', $date)->orderBy('start_date', 'desc')->first();
        $nextFortnightDate = Carbon::parse($fortnightStart->end_date)->addDay();
        $fortnights  = TwentyTwoDayInterval::where('start_date', '>=', $nextFortnightDate)->orderBy('start_date', 'asc')->limit($noOfPayrolls)->get();

        if (!$fortnightStart) {
            return response()->json(['error' => 'No matching twenty two days found for the selected start date.'], 404);
        }

        $endDate = Carbon::parse($fortnights->last()->end_date);
        $startDate = Carbon::parse($nextFortnightDate)->format('d-m-Y');

        return response()->json(['end_date' => $endDate->format('d-m-Y'), 'start_date' => $startDate]);
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // public function store(Request $request)
    // {
    //     if (!Gate::allows('create nst deduction')) {
    //         abort(403);
    //     }
    //     $request->validate([
    //         'employee_id'   => 'required|exists:users,id',
    //         'type'          => 'required|string',
    //         'amount'        => 'required|numeric|min:0',
    //         'document_date' => 'required|date',
    //         'start_date'    => 'required|date',
    //         'end_date' => 'nullable|date|after_or_equal:start_date',
    //         'employee_document' => 'nullable',
    //         'no_of_payroll' => 'nullable',
    //     ]);

    //     /*$noOfPayrolls = $request->no_of_payroll ?? 1;
    //     $oneInstallment = $request->amount / $noOfPayrolls;*/

    //     $noOfPayrolls = $request->no_of_payroll;
    //     $oneInstallment = $noOfPayrolls ? ($request->amount / $noOfPayrolls) : $request->amount;

    //     $existingDeduction = EmployeeDeduction::where('employee_id', $request->employee_id)
    //         ->where('type', $request->type)
    //         ->where(function ($query) use ($request) {
    //             $query->whereBetween('start_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
    //                 ->orWhereBetween('end_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
    //                 ->orWhere(function ($query) use ($request) {
    //                     $query->where('start_date', '<=', $this->parseDate($request->start_date))
    //                         ->where('end_date', '>=', $this->parseDate($request->end_date));
    //                 });
    //         })
    //         ->exists();

    //     if ($existingDeduction) {
    //         return redirect()->route('employee-deductions.index')->with('error', 'A deduction of this type already exists for this employee.');
    //     }

    //     $employeeDocumentPath = null;
    //     if ($request->hasFile('employee_document')) {
    //         $file = $request->file('employee_document');
    //         $filename = time() . '_' . $file->getClientOriginalName();
    //         $employeeDocumentPath = $file->storeAs('employee_documents', $filename, 'public');
    //     }

    //     EmployeeDeduction::create([
    //         'employee_id'  => $request->employee_id,
    //         'type'         => $request->type,
    //         'amount'       => $request->amount,
    //         'no_of_payroll' => $noOfPayrolls,
    //         'document_date' => $request->document_date,
    //         'start_date'   => $this->parseDate($request->start_date),
    //         'end_date'     => $this->parseDate($request->end_date),
    //         'one_installment' => $oneInstallment,
    //         'pending_balance' => $request->amount,
    //         'employee_document'   => $employeeDocumentPath,
    //     ]);

    //     return redirect()->route('employee-deductions.index')->with('success', 'Employee Deduction created successfully.');
    // }
    public function store(Request $request)
    {
        if (!Gate::allows('create employee deduction')) {
            abort(403);
        }

        $request->validate([
            'employee_id'       => 'required|exists:users,id',
            'type'              => 'required|string',
            'amount'            => 'required|numeric|min:0',
            'document_date'     => 'required|date',
            'start_date'        => 'required|date|after_or_equal:document_date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'no_of_payroll'     => 'nullable',
            'employee_document' => 'nullable',
        ]);

        // [CHANGE] If no_of_payroll is not provided, use the full amount as installment;
        // otherwise, calculate one_installment normally.
        $noOfPayrolls = $request->no_of_payroll;
        $oneInstallment = $noOfPayrolls ? ($request->amount / $noOfPayrolls) : $request->amount;

        // [CHANGE] Modify the check for an existing deduction:
        // Only add the whereBetween clauses if end_date is provided.
        $existingDeductionQuery = EmployeeDeduction::where('employee_id', $request->employee_id)
            ->where('type', $request->type);
        if ($request->filled('end_date')) {
            $existingDeductionQuery->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                    ->orWhereBetween('end_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('start_date', '<=', $this->parseDate($request->start_date))
                            ->where('end_date', '>=', $this->parseDate($request->end_date));
                    });
            });
        }
        $existingDeduction = $existingDeductionQuery->exists();

        if ($existingDeduction) {
            return redirect()->route('employee-deductions.index')
                ->with('error', 'A deduction of this type already exists for this employee.');
        }

        $employeeDocumentPath = null;
        if ($request->hasFile('employee_document')) {
            $file = $request->file('employee_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $employeeDocumentPath = $file->storeAs('employee_documents', $filename, 'public');
        }

        EmployeeDeduction::create([
            'employee_id'     => $request->employee_id,
            'type'            => $request->type,
            'amount'          => $request->amount,
            'no_of_payroll'   => $noOfPayrolls,
            'document_date'   => $request->document_date,
            'start_date'      => $this->parseDate($request->start_date),
            'end_date'        => $this->parseDate($request->end_date),
            'one_installment' => $oneInstallment,
            'pending_balance' => $request->amount,
            'employee_document' => $employeeDocumentPath,
        ]);

        return redirect()->route('employee-deductions.index')
            ->with('success', 'Employee Deduction created successfully.');
    }

    public function edit($id)
    {
        if (!Gate::allows('edit employee deduction')) {
            abort(403);
        }

        $deduction = EmployeeDeduction::findOrFail($id);

        $userRole = Role::where('id', 9)->first();

        $employees = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();

        return view('admin.employee-deductions.edit', compact('deduction', 'employees'));
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit employee deduction')) {
            abort(403);
        }

        $deduction = EmployeeDeduction::findOrFail($id);

        $request->validate([
            'employee_id'       => 'required|exists:users,id',
            'type'              => 'required|string',
            'amount'            => 'required|numeric|min:0',
            'document_date'     => 'required|date',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'no_of_payroll'     => 'nullable',
            'employee_document' => 'nullable|file',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->toDateString() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->toDateString() : null;

        $noOfPayrolls = $request->no_of_payroll;
        $oneInstallment = $noOfPayrolls ? ($request->amount / $noOfPayrolls) : $request->amount;

        $existingDeductionQuery = EmployeeDeduction::where('employee_id', $request->employee_id)
            ->where('type', $request->type)
            ->where('id', '!=', $deduction->id);

        if ($endDate) {
            $existingDeductionQuery->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });
        } else {
            $existingDeductionQuery->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->where('start_date', $startDate);
            });
        }

        if ($existingDeductionQuery->exists()) {
            return redirect()->route('employee-deductions.index')
                ->with('error', 'A deduction of this type already exists for this employee in the given date range.');
        }

        $employeeDocumentPath = $deduction->employee_document;
        if ($request->hasFile('employee_document')) {
            if ($employeeDocumentPath && Storage::disk('public')->exists($employeeDocumentPath)) {
                Storage::disk('public')->delete($employeeDocumentPath);
            }

            $file = $request->file('employee_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $employeeDocumentPath = $file->storeAs('employee_documents', $filename, 'public');
        }

        $deduction->update([
            'employee_id'       => $request->employee_id,
            'type'              => $request->type,
            'amount'            => $request->amount,
            'no_of_payroll'     => $noOfPayrolls,
            'document_date'     => Carbon::parse($request->document_date)->toDateString(),
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'one_installment'   => $oneInstallment,
            'pending_balance'   => $request->amount,
            'employee_document' => $employeeDocumentPath,
        ]);

        return redirect()->route('employee-deductions.index')
            ->with('success', 'Employee Deduction updated successfully.');
    }

    public function destroy($id)
    {
        if (!Gate::allows('delete employee deduction')) {
            abort(403);
        }
        $deduction = EmployeeDeduction::findOrFail($id);
        $deduction->delete();

        return response()->json(['success' => true]);
    }

    public function exportEmployeeDeduction(Request $request)
    {
        $searchName = $request->input('search_name');
        $searchDocumentDate = $request->input('search_document_date');
        $searchPeriodDate = $request->input('search_period_date');

        $query = EmployeeDeductionDetail::with('deduction', 'user');
        if ($searchName) {
            $query->whereHas('user', function ($q) use ($searchName) {
                $q->where('first_name', 'like', '%' . $searchName . '%')
                    ->orwhere('surname', 'like', '%' . $searchName . '%');
            });
        }

        if ($request->has('search_type') && !empty($request->search_type)) {
            $query->whereHas('deduction', function ($q) use ($request) {
                $q->where('type', $request->search_type);
            });
        }

        if ($searchDocumentDate) {
            $query->whereHas('deduction', function ($q) use ($searchDocumentDate) {
                $q->whereDate('document_date', '=', carbon::parse($searchDocumentDate)->format('Y-m-d'));
            });
        }

        if ($searchPeriodDate) {
            $query->whereHas('deduction', function ($q) use ($searchPeriodDate) {
                $q->whereDate('start_date', '<=', Carbon::parse($searchPeriodDate)->format('Y-m-d'))
                    ->whereDate('end_date', '>=', Carbon::parse($searchPeriodDate)->format('Y-m-d'));
            });
        }
        $deductionDetails = $query->get();

        return Excel::download(new EmployeeDeductionExport($deductionDetails), 'employee_deductions_report.xlsx');
    }
}
