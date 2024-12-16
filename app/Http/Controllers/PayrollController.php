<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\FortnightDates;
use App\Models\PayrollDetail;

class PayrollController extends Controller
{
    public function index()
    {
        return view('admin.payroll.index');
    }

    public function getPayroll(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        $payrolls = Payroll::with('user');
        
        if ($request->has('date') && !empty($request->date)) {
            $searchDate = Carbon::parse($request->date);
            $payrolls->whereDate('start_date', '<=', $searchDate)->whereDate('end_date', '>=', $searchDate);
        } else {
            $payrolls->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $payrolls->where(function($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_hours', 'like', '%' . $searchValue . '%')
                    ->orWhere('overtime', 'like', '%' . $searchValue . '%')
                    ->orWhere('public_holidays', '%'. $searchValue . '%')
                    ->orWhereHas('user', function($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = Payroll::count();
        $filteredRecords = $payrolls->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $payrolls = $payrolls->skip($start)->take($length)->get();
        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $payrolls,
        ];

        return response()->json($data);
    }

    public function create() {
        //
    }

    public function store(Request $request) {
        //
    }
    
    public function show(Payroll $payroll)
    {
        $payrollDetails = PayrollDetail::where('payroll_id', $payroll->id)->get();

        return view('admin.payroll.view');
    }
}
