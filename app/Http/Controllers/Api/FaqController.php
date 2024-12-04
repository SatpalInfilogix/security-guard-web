<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\HelpRequest;

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

    public function getHelpRequest()
    {
        $helpRequest = HelpRequest::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'help request.',
            'data'    => $helpRequest
        ]);
    }
}
