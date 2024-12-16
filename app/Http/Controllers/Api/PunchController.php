<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Punch;
use App\Models\RateMaster;
use App\Models\GuardRoster;
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

    protected function checkIfUserExistingInSite($userLat, $userLong, $clientLat, $clientLong, $clientRadius = 100){
        if ($userLat && $userLong) {
            $userDistance = $this->geocodingService->trackUserDistanceFromSite(
                $clientLat,
                $clientLong,
                $userLat,
                $userLong
            );
           
            if(!isset($userDistance['distance']['value'])){
                return [
                    'success' => false,
                    'status' => 'COMPANY_NOT_ALOCATED',
                    'message' => 'Unable to calculate distance from Google Maps API.'
                ];
            }

            $distanceInMeters = $userDistance['distance']['value'];
        
            $distanceInKm = $distanceInMeters / 1000;
            if ($distanceInMeters > $clientRadius) {
                return [
                    'success' => false,
                    'status' => 'OUT_OF_SITE_RADIUS',
                    'distance' => $userDistance['distance'],
                    'duration' => $userDistance['duration'],
                    'message' => 'You are too far from the assigned site. The distance is ' . round($distanceInKm, 2) . ' km.'
                ];
            }

            return null;
        }
    }

    public function logPunch(Request $request, $action)
    {
        $rules = [
            'time' => 'required|date_format:Y-m-d H:i:s',
        ];

        $today = Carbon::today();
        $todaysDuty = GuardRoster::with('clientSite')->where('guard_id', Auth::id())->whereDate('date', $today)->first();
        
        if (!$todaysDuty) {
            return response()->json([
                'success' => false,
                'status' => 'DUTY_NOT_ASSIGNED',
                'message' => 'You do not have duty scheduled for today.'
            ], 400);
        }
       
        $clientLat = $todaysDuty->clientSite->latitude;
        $clientLong = $todaysDuty->clientSite->longitude;
        $clientRadius = $todaysDuty->clientSite->radius;

        $this->addActionRules($rules, $action);
        
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 'VALIDATION_ERROR',
                'message' => $validator->errors()
            ], 400);
        }
    
        if (!in_array($action, ['in', 'out'])) {
            return response()->json([
                'success' => false,
                'status' => 'INVALID_ACTION',
                'message' => 'Invalid action value.'
            ], 400);
        }

        if ($action === 'out') {
            $punchOut = Punch::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
    
            if (!$punchOut) {
                return response()->json([
                    'success' => false,
                    'status' => 'NOT_PUNCHED_IN',
                    'message' => 'Please punch In first.'
                ], 400);
            }

            $userLat = $request->input('out_lat');
            $userLong = $request->input('out_long');

            if ($userLat && $userLong) {
                $outsideDistance = $this->checkIfUserExistingInSite($userLat, $userLong, $clientLat, $clientLong, $clientRadius);
                if($outsideDistance){
                    return response()->json($outsideDistance);
                }
            }


            $imageName = uploadFile($request->file('out_image'), 'uploads/activity/punch_out/');
            $out_location = $this->geocodingService->getAddress($request->out_lat, $request->out_long);

            $punchOut->update([
                'out_time'      => $request->time,
                'out_lat'       => $request->out_lat,
                'out_long'      => $request->out_long,
                'out_location'  => json_encode($out_location) ?? '',
                'out_image'     => $imageName,
            ]);

            return $this->createResponse(true, 'Punch updated successfully.', $punchOut);
        } else {
            $oldPunch = Punch::where('user_id', Auth::id())->whereNull('out_time')->orderBy('created_at', 'desc')->latest()->first();
            if ($oldPunch) {
                return response()->json([
                    'success' => false,
                    'status' => 'ALREADY_PUNCHED_IN',
                    'message' => 'You are already Punch In.'
                ], 400);
            }

            $userLat = $request->input('in_lat');
            $userLong = $request->input('in_long');

            if ($userLat && $userLong) {
                $outsideDistance = $this->checkIfUserExistingInSite($userLat, $userLong, $clientLat, $clientLong, $clientRadius);
                if($outsideDistance){
                    return response()->json($outsideDistance);
                }
            }

            $imageName = uploadFile($request->file('in_image'), 'uploads/activity/punch_in/');
            $in_location = $this->geocodingService->getAddress($request->in_lat, $request->in_long);

            $rateMaster = RateMaster::where('id', $todaysDuty->guard_type_id)->first();
            $punchIn = Punch::create([
                'user_id'       => Auth::id(),
                'guard_type_id' => optional($rateMaster)->id,
                'in_time'       => $request->time,
                'in_lat'        => $request->in_lat,
                'in_long'       => $request->in_long,
                'in_location'   => json_encode($in_location) ?? '',
                'in_image'      => $imageName,
                'regular_rate'  => $rateMaster->regular_rate ?? 0,
                'laundry_allowance' => $rateMaster->laundry_allowance ?? 0,
                'canine_premium'    => $rateMaster->canine_premium ?? 0,
                'fire_arm_premium'  => $rateMaster->fire_arm_premium ?? 0,
                'gross_hourly_rate' => $rateMaster->gross_hourly_rate ?? 0,
                'overtime_rate'     => $rateMaster->overtime_rate ?? 0,
                'holiday_rate'      => $rateMaster->holiday_rate ?? 0
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

    public function calculateOvertime($userId)
    {
        $punchRecords = Punch::where('user_id', $userId)->get();
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
