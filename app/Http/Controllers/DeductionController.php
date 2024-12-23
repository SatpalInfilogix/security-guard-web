<?php

namespace App\Http\Controllers;

use App\Models\Deduction;
use App\Models\User;
use App\Models\FortnightDates;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DeductionController extends Controller
{
    public function index()
    {
        $deductions = Deduction::with('user')->latest()->get();

        return view('admin.deductions.index', compact('deductions'));
    }

    public function create()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();
       
        return view('admin.deductions.create', compact('securityGuards'));
    }

    public function getEndDate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $noOfPayrolls = $request->no_of_payroll ?? 1;
        $fortnightStart = FortnightDates::where('start_date', '<=', $startDate)->orderBy('start_date', 'desc')->first();
        $fortnights  = FortnightDates::where('start_date', '>=', $fortnightStart->start_date)->orderBy('start_date', 'asc')->limit($noOfPayrolls)->get();

        if (!$fortnightStart) {
            return response()->json(['error' => 'No matching fortnight found for the selected start date.'], 404);
        }

        $endDate = Carbon::parse($fortnights->last()->end_date);

        return response()->json(['end_date' => $endDate->format('d-m-Y')]);
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

    public function store(Request $request)
    {
        $request->validate([
            'guard_id'    => 'required|exists:users,id',
            'type'        => 'required|string',
            'amount'      => 'required|numeric|min:0',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        $noOfPayrolls = $request->no_of_payroll ?? 1;
        $oneInstallment = $request->amount / $noOfPayrolls;

        $existingDeduction = Deduction::where('guard_id', $request->guard_id)
            ->where('type', $request->type)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                      ->orWhereBetween('end_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                      ->orWhere(function($query) use ($request) {
                          $query->where('start_date', '<=', $this->parseDate($request->start_date))
                                ->where('end_date', '>=', $this->parseDate($request->end_date));
                      });
            })
            ->exists();

        if ($existingDeduction) {
            return redirect()->route('deductions.index')->with('error', 'A deduction of this type already exists for this guard.');
        }

        Deduction::create([
            'guard_id'     => $request->guard_id,
            'type'         => $request->type,
            'amount'       => $request->amount,
            'no_of_payroll'=> $noOfPayrolls,
            'start_date'   => $this->parseDate($request->start_date),
            'end_date'     => $this->parseDate($request->end_date),
            'one_installment' => $oneInstallment,
            'pending_balance' => $request->amount
        ]);

        return redirect()->route('deductions.index')->with('success', 'Deduction created successfully.');
    }
}
