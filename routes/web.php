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
use App\Http\Controllers\FortnightDatesController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\EmployeeRateMasterController;
use App\Http\Controllers\EmployeeLeavesController;
use App\Http\Controllers\EmployeePayrollController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\EmployeeOvertimeController;
use App\Http\Controllers\EmployeeTaxThresholdController;
use App\Http\Controllers\GuardLeaveEncashmentController;
use App\Http\Controllers\GuardTaxThresholdController;
use App\Http\Controllers\LeaveEncashmentController;
use App\Http\Controllers\UserActivityController;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
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
            'leaves'                => LeaveController::class,
            'fortnight-dates'       => FortnightDatesController::class,
            'payrolls'              => PayrollController::class,
            'deductions'            => DeductionController::class,
            'invoices'              => InvoiceController::class,
            'employees'             => EmployeeController::class,
            'employee-rate-master'  => EmployeeRateMasterController::class,
            'employee-leaves'       => EmployeeLeavesController::class,
            'employee-payroll'      => EmployeePayrollController::class,
            'employee-deductions'   => EmployeeDeductionController::class,
            'employee-overtime'     => EmployeeOvertimeController::class,
            'employee-leave-encashment'  => LeaveEncashmentController::class,
            'guard-leave-encashment'  => GuardLeaveEncashmentController::class,
            'employee-tax-threshold'  => EmployeeTaxThresholdController::class,
            'guard-tax-threshold'   =>   GuardTaxThresholdController::class,
        ]);
        Route::get('employee-leaves/{id}/{batchId?}/edit', [EmployeeLeavesController::class, 'edit'])->name('employee-leaves.modify');
        Route::put('employee-leaves/{id}/{batchId?}', [EmployeeLeavesController::class, 'update'])->name('employee-leaves.update');
        Route::delete('employee-leaves/{id}/{batchId?}', [EmployeeLeavesController::class, 'destroy'])->name('employee-leaves.destroy');

        Route::get('leaves/{guardId}/edit/{batchId}', [LeaveController::class, 'edit'])->name('leaves.modify');
        //Route::get('leaves/{id}/{date?}/edit', [LeaveController::class, 'edit'])->name('leaves.modify');
        Route::put('leaves/{guardId}/{batchId}', [LeaveController::class, 'update'])->name('leaves.update');
        Route::delete('/leaves/{guardId}/{batchId?}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
        Route::get('send-leave-notification/{id?}/{status?}', [LeaveController::class, 'sendNotificationAfterLeave'])->name('leaves.sendNotification');

        Route::get('/get-pending-leaves', [LeaveEncashmentController::class, 'getPendingLeaves'])->name('get-pending-leaves');
        Route::get('guard-leave-encashments/get-pending-leaves', [GuardLeaveEncashmentController::class, 'getPendingLeaves'])->name('get-guard-pending-leaves');

        Route::get('employee-overtime/{employee_id}/{date}/edit', [EmployeeOvertimeController::class, 'edit'])->name('employee-overtime.edit');
        Route::put('employee-overtime/{employee_id}/{date}', [EmployeeOvertimeController::class, 'update'])->name('employee-overtime.update');
        Route::delete('employee-overtime/{employee_id}/{id}', [EmployeeOvertimeController::class, 'destroy'])->name('employee-overtime.destroy');

        Route::get('/payment-settings', [SettingController::class, 'paymentSetting'])->name('settings.payment-settings');
        Route::get('/gerenal-settings', [SettingController::class, 'generalSettings'])->name('settings.gerenal-settings');

        Route::get('/roles-and-permissions/role-list', [RoleAndPermissionController::class, 'show'])->name('roles-and-permissions.role-list');
        Route::post('/roles-and-permissions/store-role', [RoleAndPermissionController::class, 'storeRole'])->name('roles-and-permissions.store-role');
        // Route::post('import-guards', [SecurityGuardController::class, 'importGuards'])->name('import.guards');
        Route::view('/calendar-management', 'admin.calendar-management.index')->name('calendar.management');

        Route::post('/generate-client-code', [ClientController::class, 'generateClientCode'])->name('generate.client.code');
        Route::get('/twenty-two-days-interval', [FortnightDatesController::class, 'listingTwentyTwoDays'])->name('get-interval');
    });

    Route::get('/get-client-sites/{clientId}', [GuardRosterController::class, 'getClientSites']);
    Route::get('/get-assigned-dates/{guardId}', [GuardRosterController::class, 'getAssignedDate']);
    Route::put('/users/update-status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::get('/get-public-holidays', [GuardRosterController::class, 'getPublicHolidays']);
    Route::get('/get-leaves/{guardId}', [GuardRosterController::class, 'getLeaves']);
    Route::get('/get-guard-roster-details', [GuardRosterController::class, 'getGuardRosterDetails'])->name('get.guard.roster.details');

    //************************Import Csv Route********************/
    Route::post('import-payroll', [PayrollController::class, 'importPayroll'])->name('import.payroll');
    Route::post('import-client-site', [ClientSiteController::class, 'importClientSite'])->name('import.client-site');
    Route::post('import-guard-roster', [GuardRosterController::class, 'importGuardRoster'])->name('import.guard-roster');
    Route::post('import-security-guard', [SecurityGuardController::class, 'importSecurityGuard'])->name('import.security-guard');
    Route::post('employee-leave-encashment/import', [LeaveEncashmentController::class, 'import'])->name('employee-leave-encashment.import');
    Route::get('employee-leave-encashment/export-results', [LeaveEncashmentController::class, 'exportResultCsv'])->name('employee-leave-encashment.export-results');
    Route::get('/download-leave-encashment-sample', [LeaveEncashmentController::class, 'downloadSample'])->name('leave-encashment.sample');
    Route::post('guard-leave-encashment/import', [GuardLeaveEncashmentController::class, 'import'])->name('guard-leave-encashment.import');
    Route::get('guard-leave-encashment/export-results', [GuardLeaveEncashmentController::class, 'exportResultCsv'])
        ->name('guard-leave-encashment.export-results');
    Route::get('/download-guard-leave-encashment-sample', [GuardLeaveEncashmentController::class, 'downloadSample'])
        ->name('guard-leave-encashment.sample');
    Route::get('/employee-payroll/export', [EmployeePayrollController::class, 'export'])->name('employee-payroll.export');
    Route::get('payroll-export/guard', [PayrollController::class, 'exportGuardPayroll'])->name('payroll-export.guard');



    Route::get('download-guard-roster-sample', function () {
        $file = public_path('assets/sample-guard-roster/guard_roster.xlsx');
        return Response::download($file);
    });

    Route::get('download-guard-sample', function () {
        $file = public_path('assets/sample-security-guard/security-guard.csv');
        return Response::download($file);
    });

    Route::get('download-payroll-sample', function () {
        $file = public_path('assets/sample-payroll/payroll.csv');
        return Response::download($file);
    });
    Route::get('download-client-site-sample', function () {
        $file = public_path('assets/sample-client-site/client-site.csv');
        return Response::download($file);
    });

    Route::get('download-employee-sample', function () {
        $file = public_path('assets/sample-employee/employee.csv');
        return Response::download($file);
    });

    Route::get('/export/csv', [GuardRosterController::class, 'downloadExcel'])->name('export.csv');
    Route::get('export-guards', [SecurityGuardController::class, 'exportGuards'])->name('export.guards');
    Route::get('export-clients', [ClientSiteController::class, 'exportClients'])->name('export.client');
    Route::get('/client-site/download', [ClientSiteController::class, 'download'])->name('client-site.download');
    Route::get('/security-guards/filter', [SecurityGuardController::class, 'filter'])->name('security-guards.filter');

    Route::get('/payroll-export/csv', [PayrollController::class, 'payrollExport'])->name('payroll-export.csv');
    Route::get('/payrolls/download', [PayrollController::class, 'download'])->name('payrolls.download');

    Route::post('get-security-guard', [SecurityGuardController::class, 'getSecurityGuard'])->name('get-security-guard');
    Route::post('get-client-list', [ClientController::class, 'getClient'])->name('get-client-list');
    Route::post('get-client-site-list', [ClientSiteController::class, 'getClientSite'])->name('get-client-site-list');
    Route::post('get-payroll-list', [PayrollController::class, 'getPayroll'])->name('get-payroll-list');
    Route::post('get-deductions-list', [DeductionController::class, 'getDeductionsData'])->name('get-deductions-list');
    Route::post('get-invoice-list', [InvoiceController::class, 'getInvoice'])->name('get-invoice-list');
    Route::post('get-leaves-list', [LeaveController::class, 'getLeave'])->name('get-leaves-list');

    Route::get('security-guards/pdf', [SecurityGuardController::class, 'downloadPDF'])->name('security-guards.pdf');

    Route::post('/leaves/{leaveId}/update-status', [LeaveController::class, 'updateStatus'])->name('leaves.updateStatus');

    Route::get('/guard-rosters/download', [GuardRosterController::class, 'download'])->name('guard-rosters.download');
    Route::get('/security-guard/download', [SecurityGuardController::class, 'exportResultCsv'])->name('security-guard.download');
    Route::get('/attendance-list/download', [AttendanceController::class, 'exportAttendance'])->name('attendance-list.download');

    Route::get('/get-guard-type-by-guard-id/{guardId}', [GuardRosterController::class, 'getGuardTypeByGuardId']);

    Route::get('/get-end-date', [DeductionController::class, 'getEndDate']);

    Route::get('export-deduction', [DeductionController::class, 'exportDeduction'])->name('export.deductions');

    Route::get('invoice/{id}/download-pdf', [InvoiceController::class, 'downloadPdf'])->name('invoice.download-pdf');
    Route::get('/export-csv', [InvoiceController::class, 'exportCsv'])->name('invoice.export-csv');
    Route::post('/invoice/update-status', [InvoiceController::class, 'updateStatus'])->name('invoice.update-status');
    Route::get('/get-client-sites', [InvoiceController::class, 'getClientSites'])->name('get-client-sites');

    Route::get('payrolls/{id}/download-pdf', [PayrollController::class, 'downloadPdf'])->name('payrolls.download-pdf');
    Route::get('payrolls/bulk-download', [PayrollController::class, 'bulkDownloadPdf'])->name('payrolls.bulk-download-pdf');

    Route::post('get-employee', [EmployeeController::class, 'getEmployee'])->name('get-employee');
    Route::post('/employees/employee-status', [EmployeeController::class, 'employeeStatus'])->name('employees.employee-status');
    Route::get('employees/pdf', [EmployeeController::class, 'downloadPDF'])->name('employees.pdf');
    Route::get('export-employee', [EmployeeController::class, 'exportEmployees'])->name('export.employee');
    Route::post('import-employee', [EmployeeController::class, 'importEmployee'])->name('import.employee');
    Route::get('/employees/download', [EmployeeController::class, 'exportResultCsv'])->name('employees.download');

    Route::post('get-employee-leaves-list', [EmployeeLeavesController::class, 'getEmployeeLeaves'])->name('get-employee-leaves-list');
    Route::post('employee-leaves/{leaveId}/update-status', [EmployeeLeavesController::class, 'updateLeaveStatus'])->name('employee-leaves.updateStatus');

    Route::post('get-employee-payroll-list', [EmployeePayrollController::class, 'getEmployeePayroll'])->name('get-employee-payroll-list');
    Route::get('employee-payroll/bulk-download', [EmployeePayrollController::class, 'bulkDownloadPdf'])->name('employee-payroll.bulk-download-pdf');
    Route::get('employee-payroll/{id}/download-pdf', [EmployeePayrollController::class, 'downloadPdf'])->name('employee-payroll.download-pdf');
    Route::get('/employee-payroll-export/csv', [EmployeePayrollController::class, 'employeePayrollExport'])->name('employee-payroll-export.csv');
    Route::get('/payroll/export-nst-deductions', [EmployeePayrollController::class, 'exportNSTDeductions'])->name('employee-payroll.export-nst-deductions');

    Route::get('/get-employee-end-date', [EmployeeDeductionController::class, 'getEndDate'])->name('get-employee-end-date');
    Route::post('get-employee-deductions-list', [EmployeeDeductionController::class, 'getDeductionsData'])->name('get-employee-deductions-list');
    Route::get('export-employee-deduction', [EmployeeDeductionController::class, 'exportEmployeeDeduction'])->name('export.employee-deductions');
});

Route::get('activities', [UserActivityController::class, 'index'])
    ->name('activities.index')
    ->middleware('auth');
