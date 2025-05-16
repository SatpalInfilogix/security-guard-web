<?php

namespace App\Http\Controllers;

use App\Models\LeaveEncashment;
use Illuminate\Http\Request;

class LeaveEncashmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.leave-encashment.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.leave-encashment.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveEncashment $leaveEncashment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveEncashment $leaveEncashment)
    {
        return view('admin.leave-encashment.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveEncashment $leaveEncashment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveEncashment $leaveEncashment)
    {
        //
    }
}
