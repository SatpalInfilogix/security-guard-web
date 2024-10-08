<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faq = Faq::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'faq list.',
            'data'    => $faq
        ]);
    }
}
