<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use Illuminate\Support\Facades\Gate;

class FaqController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view faq')) {
            abort(403);
        }
        $faqs = Faq::latest()->get();

        return view('admin.faq.index', compact('faqs'));
    }

    public function create()
    {
        if(!Gate::allows('create faq')) {
            abort(403);
        }
        return view('admin.faq.create');
    }

    public function store(Request $request)
    {
        if(!Gate::allows('create faq')) {
            abort(403);
        }
        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
        ]);

        Faq::create([
            'question' => $request->question,
            'answer'   => $request->answer
        ]);

        return redirect()->route('faq.index')->with('success', 'FAQ created successfully');
    }

    public function show(string $id)
    {
        //
    }

    public function edit($id)
    {
        if(!Gate::allows('edit faq')) {
            abort(403);
        }
        $faq = Faq::where('id', $id)->first();

        return view('admin.faq.edit', compact('faq'));
    }

    public function update(Request $request, $id)
    {
        if(!Gate::allows('edit faq')) {
            abort(403);
        }
        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
        ]);

        $faq = Faq::where('id', $id)->first();
        $faq->update([
            'question' => $request->question,
            'answer'   => $request->answer
        ]);

        return redirect()->route('faq.index')->with('success', 'Faq Updated successfully.');
    }

    public function destroy(string $id)
    {
        if(!Gate::allows('delete faq')) {
            abort(403);
        }
        $faq = Faq::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Faq deleted successfully.'
        ]);
    }
}
