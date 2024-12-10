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
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RateMasterController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientSiteController;
use App\Http\Controllers\GuardRosterController;
use App\Http\Controllers\HelpRequestController;
use App\Http\Controllers\LeaveController;

Route::get('/', function (){
    return redirect()->route('admin.dashboard.index');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
        Route::post('/get-guard-roster', [GuardRosterController::class, 'getGuardRosters'])->name('get-guard-roster');
        Route::post('/get-guard-roster-list', [GuardRosterController::class, 'getGuardRosterList'])->name('get-guard-roster-list');
        
        Route::resources([
            'profile'               => ProfileController::class,
            'users'                 => UserController::class,
            'settings'              => SettingController::class,
            'faq'                   => FaqController::class,
            'attendance'            => AttendanceController::class,
            'security-guards'       => SecurityGuardController::class,
            'roles-and-permissions' => RoleAndPermissionController::class,
            'rate-master'           => RateMasterController::class,
            'public-holidays'       => PublicHolidayController::class,
            'help_requests'         => HelpRequestController::class,
            'clients'               => ClientController::class,
            'client-sites'          => ClientSiteController::class,
            'guard-rosters'         => GuardRosterController::class,
            'leaves'                => LeaveController::class
        ]);

        Route::get('/payment-settings', [SettingController::class, 'paymentSetting'])->name('settings.payment-settings');
        Route::get('/gerenal-settings', [SettingController::class, 'generalSettings'])->name('settings.gerenal-settings');

        Route::get('/roles-and-permissions/role-list', [RoleAndPermissionController::class,'show'])->name('roles-and-permissions.role-list');
        Route::post('/roles-and-permissions/store-role', [RoleAndPermissionController::class,'storeRole'])->name('roles-and-permissions.store-role');
        // Route::post('import-guards', [SecurityGuardController::class, 'importGuards'])->name('import.guards');
        Route::view('/calendar-management','admin.calendar-management.index')->name('calendar.management');
       
        Route::post('/generate-client-code', [ClientController::class, 'generateClientCode'])->name('generate.client.code');
    });

    Route::get('/get-client-sites/{clientId}', [GuardRosterController::class, 'getClientSites']);
    Route::get('/get-assigned-dates/{guardId}', [GuardRosterController::class, 'getAssignedDate']);
    Route::put('/users/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::get('/get-public-holidays', [GuardRosterController::class, 'getPublicHolidays']);
    Route::get('/get-leaves/{guardId}', [GuardRosterController::class, 'getLeaves']);
    Route::get('/get-guard-roster-details', [GuardRosterController::class, 'getGuardRosterDetails'])->name('get.guard.roster.details');

    //************************Import Csv Route********************/
    Route::post('import-guard-roster', [GuardRosterController::class, 'importGuardRoster'])->name('import.guard-roster');
    Route::post('import-security-guard', [SecurityGuardController::class, 'importSecurityGuard'])->name('import.security-guard');

    Route::get('download-guard-roster-sample', function() {
        $file = public_path('assets/sample-guard-roster/guard-roster.xlsx');
        return Response::download($file);
    });

    Route::get('download-guard-sample', function() {
        $file = public_path('assets/sample-security-guard/security-guard.csv');
        return Response::download($file);
    });

    Route::get('/export/csv', [GuardRosterController::class, 'downloadExcel'])->name('export.csv');
    Route::get('export-guards', [SecurityGuardController::class, 'exportGuards'])->name('export.guards');
    Route::get('/security-guards/filter', [SecurityGuardController::class, 'filter'])->name('security-guards.filter');
   
    Route::post('get-security-guard', [SecurityGuardController::class, 'getSecurityGuard'])->name('get-security-guard');
    Route::post('get-client-list', [ClientController::class, 'getClient'])->name('get-client-list');
    Route::post('get-client-site-list', [ClientSiteController::class, 'getClientSite'])->name('get-client-site-list');

    Route::get('security-guards/pdf', [SecurityGuardController::class, 'downloadPDF'])->name('security-guards.pdf');

    Route::post('/leaves/{leaveId}/update-status', [LeaveController::class, 'updateStatus'])->name('leaves.updateStatus');

    Route::get('/guard-rosters/download', [GuardRosterController::class, 'download'])->name('guard-rosters.download');
    Route::get('/security-guard/download', [SecurityGuardController::class, 'exportResultCsv'])->name('security-guard.download');
    Route::get('/attendance-list/download', [AttendanceController::class, 'exportAttendance'])->name('attendance-list.download');
});
