<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    public function getPayroll()
    {
        $payrolls = Payroll::where('guard_id', Auth::id())->latest()->get();

        return response()->json([
            'success' =>true,
            'message' => 'Payroll listing.',
            'data'    => $payrolls
        ]);
    }

    public function payrollById($id)
    {
        $payroll = Payroll::where('guard_id', Auth::id())->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payroll.',
            'data'    => $payroll
        ]);
    }
}
