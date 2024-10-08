<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleAndPermissionController;
use App\Http\Controllers\SecurityGuardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\FaqController;

Route::get('/', function (){
    return redirect()->route('admin.dashboard.index');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
        Route::resources([
            'profile'    => ProfileController::class,
            'users'      => UserController::class,
            'roles-and-permissions' => RoleAndPermissionController::class,
            'security-guards' => SecurityGuardController::class,
            'settings'        => SettingController::class,
            'faq'             => FaqController::class
        ]);
        Route::get('export-guards', [SecurityGuardController::class, 'exportGuards'])->name('export.guards');
        Route::post('import-guards', [SecurityGuardController::class, 'importGuards'])->name('import.guards');
    });
});