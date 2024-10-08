<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\PunchController;
use App\Http\Controllers\Api\ProfileController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/punch/{action}', [PunchController::class, 'logPunch']);
    Route::post('edit-profile', [ProfileController::class,'editProfile']);
    Route::get('guard/profile',[ProfileController::class,'guardProfile']);
});
