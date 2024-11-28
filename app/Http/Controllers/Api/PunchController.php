<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PunchTable;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PunchController extends Controller
{
    protected $geocodingService;
    protected $officeLatitude = 30.7093774; // Your office latitude
    protected $officeLongitude = 76.6921674; // Your office longitude

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    public function logPunch(Request $request, $action)
    {
        $rules = [
            'time' => 'required|date_format:Y-m-d H:i:s',
        ];
        
        $this->addActionRules($rules, $action);
        
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 400);
        }
    
        if (!in_array($action, ['in', 'out'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action value.'
            ], 400);
        }

        if ($action === 'out') {
            $punchOut = PunchTable::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
    
            if (!$punchOut) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please punch In first.'
                ], 400);
            }

            $imageName = uploadFile($request->file('out_image'), 'uploads/activity/punch_out/');
            $out_location = $this->getLocationFromLatLng($request->out_lat, $request->out_long);
    
            $punchOut->update([
                'out_time'      => $request->time,
                'out_lat'       => $request->out_lat,
                'out_long'      => $request->out_long,
                'out_location'  => json_encode($out_location) ?? '',
                'out_image'     => $imageName,
            ]);

            return $this->createResponse(true, 'Punch updated successfully.', $punchOut);
        } else {
            $oldPunch = PunchTable::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
            if ($oldPunch) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already Punch In.'
                ], 400);
            }

            $imageName = uploadFile($request->file('in_image'), 'uploads/activity/punch_in/');
            $in_location = $this->getLocationFromLatLng($request->in_lat, $request->in_long);

            $punchIn = PunchTable::create([
                'user_id'     => Auth::id(),
                'in_time'     => $request->time,
                'in_lat'      => $request->in_lat,
                'in_long'     => $request->in_long,
                'in_location' => json_encode($in_location) ?? '',
                'in_image'    => $imageName,
            ]);

            return $this->createResponse(true, 'Punch created successfully.', $punchIn);
        }
    }

    /// Validations
    private function addActionRules(&$rules, $action)
    {
        if ($action === 'in') {
            $rules['in_lat']      = 'required';
            $rules['in_long']     = 'required';
            // $rules['in_location'] = 'required';
            $rules['in_image']    = 'required|file|image|mimes:jpg,jpeg,png,gif';
        } elseif ($action === 'out') {
            $rules['out_lat']      = 'required';
            $rules['out_long']     = 'required';
            // $rules['out_location'] = 'required';
            $rules['out_image']    = 'required|file|image|mimes:jpg,jpeg,png,gif';
        }
    }

    private function createResponse($success, $message, $data = null)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    private function getLocationFromLatLng($lat, $lng)
    {
        $apiKey = env('GOOGLE_API_KEY');
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();
            return $data['results'][0] ?? null;
        }

        return null;
    }

    public function getAddress(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $address = $this->geocodingService->getAddress($latitude, $longitude);
        return response()->json(['address' => $address]);
    }

    public function checkDistanceFromOffice(Request $request)
    {
        $rules = [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 400);
        }

        $inputLatitude  = $request->input('latitude');
        $inputLongitude = $request->input('longitude');

        $distance = $this->geocodingService->haversineGreatCircleDistance(
            $this->officeLatitude,
            $this->officeLongitude,
            $inputLatitude,
            $inputLongitude
        );

        if ($distance <= 500) {
            return response()->json([
                'success'  => true,
                'message'  => 'You are within 500 meters of the office.',
                'distance' => $distance]);
        } else {
            return response()->json([
                'success'  => false,
                'message'  => 'You are more than 500 meters away from the office.',
                'distance' => $distance
            ]);
        }
    }

    public function calculateOvertime($userId)
    {
        $punchRecords = PunchTable::where('user_id', $userId)->get();
        $overtimeHours = 0;

        foreach ($punchRecords as $record) {
            if ($record->out_time && $record->in_time) {
                $inTime = Carbon::parse($record->in_time);
                $outTime = Carbon::parse($record->out_time);

                if ($inTime < $outTime) {
                    $totalWorkedHours = $inTime->diffInHours($outTime);

                    if ($totalWorkedHours > 8) {
                        $overtimeHours += $totalWorkedHours - 8;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Total overtime hours calculated.',
            'overtime_hours' => $overtimeHours
        ]);
    }

}
