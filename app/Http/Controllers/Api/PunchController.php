<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PunchTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SettingHelper;

class PunchController extends Controller
{
    public function logPunch(Request $request, $action)
    {
        $rules = [
            'time' => 'required|date_format:Y-m-d H:i:s',
        ];

        $this->addActionRules($rules, $action);
    
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 400);
        }
    
        if (!in_array($action, ['in', 'out'])) {
            return response()->json(['success' => false, 'message' => 'Invalid action value.'], 400);
        }

        if ($action === 'out') {
            $lastInPunch = PunchTable::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
    
            if (!$lastInPunch) {
                return response()->json(['success' => false, 'message' => 'Please punch In first.'], 400);
            }
            $lastInPunch->update([
                'out_time' =>  $request->time,
                'out_lat' => $request->out_lat,
                'out_long' => $request->out_long,
                'out_location' => $request->out_location,
                'out_image' => SettingHelper::uploadFile($request->file('out_image')),
            ]);

            return $this->createResponse(true, 'Punch updated successfully.', $lastInPunch);
        } else {
            $oldPunch = PunchTable::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
            if ($oldPunch) {
                return response()->json(['success' => false, 'message' => 'You are already Punch In.'], 400);
            }
    
            $punch = PunchTable::create([
                'user_id' => Auth::id(),
                'in_time' => $request->time,
                'in_lat' => $request->in_lat,
                'in_long' => $request->in_long,
                'in_location' => $request->in_location,
                'in_image' => SettingHelper::uploadFile($request->file('in_image')),
            ]);

            return $this->createResponse(true, 'Punch created successfully.', $punch);
        }
    }

    /// Validations
    private function addActionRules(&$rules, $action)
    {
        if ($action === 'in') {
            $rules['in_lat'] = 'required';
            $rules['in_long'] = 'required';
            $rules['in_location'] = 'required';
            $rules['in_image'] = 'required|file';
        } elseif ($action === 'out') {
            $rules['out_lat'] = 'required';
            $rules['out_long'] = 'required';
            $rules['out_location'] = 'required';
            $rules['out_image'] = 'required|file';
        }
    }

    private function createResponse($success, $message, $data = null)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
