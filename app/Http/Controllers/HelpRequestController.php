<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpRequestController extends Controller
{
    public function index()
    {
        $helpRequest = HelpRequest::where('user_id', Auth::id())->first();

        return view('admin.help-requests.index', compact('helpRequest'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
        ]);
    
        HelpRequest::updateOrCreate(
            ['user_id' => Auth::id()], // This is the condition to find the existing record
            ['description' => $request->description] // This is the data to create or update
        );
    
        return redirect()->route('help_requests.index')->with('success', 'Help request submitted successfully.');
    }
}

