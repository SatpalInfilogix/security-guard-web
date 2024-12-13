<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::with('user')->latest()->get();
        
        return view('admin.payroll.index', compact('payrolls'));
    }
}
