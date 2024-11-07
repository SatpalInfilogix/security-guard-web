<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PunchController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GeocodeController;
use App\Http\Controllers\Api\SecurityGuardController;
use App\Http\Controllers\Api\AttendanceController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/punch/{action}', [PunchController::class, 'logPunch']);
    Route::post('edit-profile', [ProfileController::class,'editProfile']);
    Route::get('guard/profile',[ProfileController::class,'guardProfile']);
    Route::get('check-status', [SecurityGuardController::class, 'checkStatus']);
    Route::get('/overtime/{userId}', [PunchController::class, 'calculateOvertime']);
    Route::post('get-attendance', [AttendanceController::Class, 'getAttendance']);
    
});
Route::get('faq', [FaqController::class, 'index']);
Route::get('get-address',[PunchController::class,'getAddress']);
Route::post('/check-distance', [PunchController::class, 'checkDistanceFromOffice']);
