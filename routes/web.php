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
use App\Http\Controllers\GuardRoasterController;
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
            'guard-roasters'        => GuardRoasterController::class,
            'leaves'                => LeaveController::class
        ]);

        Route::get('payment-settings', [SettingController::class, 'paymentSetting'])->name('settings.payment-settings');
        Route::get('gerenal-settings', [SettingController::class, 'generalSettings'])->name('settings.gerenal-settings');

        Route::get('roles-and-permissions/role-list', [RoleAndPermissionController::class,'show'])->name('roles-and-permissions.role-list');
        Route::post('roles-and-permissions/store-role', [RoleAndPermissionController::class,'storeRole'])->name('roles-and-permissions.store-role');
        // Route::post('import-guards', [SecurityGuardController::class, 'importGuards'])->name('import.guards');
        Route::view('calendar-management','admin.calendar-management.index')->name('calendar.management');
       
        Route::post('/generate-client-code', [ClientController::class, 'generateClientCode'])->name('generate.client.code');
    });
    Route::get('/get-client-sites/{clientId}', [GuardRoasterController::class, 'getClientSites']);
    Route::get('/get-assigned-dates/{guardId}', [GuardRoasterController::class, 'getAssignedDate']);
    Route::get('/get-public-holidays', [GuardRoasterController::class, 'getPublicHolidays']);
    Route::get('/get-leaves/{guardId}', [GuardRoasterController::class, 'getLeaves']);
    Route::get('/get-guard-roster-details', [GuardRoasterController::class, 'getGuardRoasterDetails'])->name('get.guard.roster.details');

    //************************Import Csv Route********************/
    Route::post('import-guard-roaster', [GuardRoasterController::class, 'importGuardRoaster'])->name('import.guard-roaster');
    Route::post('import-security-guard', [SecurityGuardController::class, 'importSecurityGuard'])->name('import.security-guard');

    Route::get('download-guard-roaster-sample', function() {
        $file = public_path('assets/sample-guard-roaster/guard-roaster.xlsx');
        return Response::download($file);
    });

    Route::get('/export/csv', [GuardRoasterController::class, 'downloadExcel'])->name('export.csv');
    Route::get('export-guards', [SecurityGuardController::class, 'exportGuards'])->name('export.guards');
    Route::get('/security-guards/filter', [SecurityGuardController::class, 'filter'])->name('security-guards.filter');

    Route::get('security-guards/pdf', [SecurityGuardController::class, 'downloadPDF'])->name('security-guards.pdf');

    Route::post('/leaves/{leaveId}/update-status', [LeaveController::class, 'updateStatus'])->name('leaves.updateStatus');

    Route::get('/guard-roasters/download', [GuardRoasterController::class, 'download'])->name('guard-roasters.download');
    Route::get('/security-guard/download', [SecurityGuardController::class, 'exportResultCsv'])->name('security-guard.download');
});
