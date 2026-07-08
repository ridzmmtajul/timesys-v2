<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\OfficeDivisionController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\TitleController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PostNumberController;
use App\Http\Controllers\ScheduleTypeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\WorkTimeRuleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\SyncController;

Route::post('/login', [AuthController::class, 'login']);

// Sync receivers — API key auth, no Sanctum (server-to-server)
Route::middleware('sync.api_key')->group(function () {
    Route::post('/sync/receive-employees', [SyncController::class, 'receiveEmployees']);
    Route::post('/sync/receive-offices', [SyncController::class, 'receiveOffices']);
    Route::post('/sync/receive-office-divisions', [SyncController::class, 'receiveOfficeDivisions']);
    Route::post('/sync/receive-work-schedules', [SyncController::class, 'receiveWorkSchedules']);
    Route::post('/sync/receive-attendances', [SyncController::class, 'receiveAttendances']);
});

Route::middleware(['auth:sanctum', 'extend.token'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);

    Route::post('/attendance/import', [AttendanceController::class, 'import']);
    Route::get('/attendance', [AttendanceController::class, 'index']);

    Route::get('/biometrics', [BiometricController::class, 'index']);
    Route::post('/biometrics', [BiometricController::class, 'store']);
    Route::get('/biometrics/{biometric}', [BiometricController::class, 'show']);
    Route::put('/biometrics/{biometric}', [BiometricController::class, 'update']);
    Route::delete('/biometrics/{biometric}', [BiometricController::class, 'destroy']);
    Route::post('/biometrics/{biometric}/connect', [BiometricController::class, 'connect']);
    Route::post('/biometrics/{biometric}/disconnect', [BiometricController::class, 'disconnect']);
    Route::post('/biometrics/{biometric}/refresh', [BiometricController::class, 'refresh']);
    Route::post('/biometrics/{biometric}/sync-time', [BiometricController::class, 'syncTime']);
    Route::get('/biometrics/{biometric}/download-log', [BiometricController::class, 'downloadLog']);
    Route::post('/biometric/pull', [BiometricController::class, 'pullLogs']);

    Route::get('/users/options', [UserController::class, 'options']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    Route::get('/offices/options', [OfficeController::class, 'options']);
    Route::get('/offices', [OfficeController::class, 'index']);
    Route::post('/offices', [OfficeController::class, 'store']);
    Route::put('/offices/{id}', [OfficeController::class, 'update']);
    Route::delete('/offices/{id}', [OfficeController::class, 'destroy']);

    Route::get('/office-divisions', [OfficeDivisionController::class, 'index']);
    Route::post('/office-divisions', [OfficeDivisionController::class, 'store']);
    Route::put('/office-divisions/{id}', [OfficeDivisionController::class, 'update']);
    Route::delete('/office-divisions/{id}', [OfficeDivisionController::class, 'destroy']);

    Route::get('/employment-types', [EmploymentTypeController::class, 'index']);
    Route::post('/employment-types', [EmploymentTypeController::class, 'store']);
    Route::put('/employment-types/{id}', [EmploymentTypeController::class, 'update']);
    Route::delete('/employment-types/{id}', [EmploymentTypeController::class, 'destroy']);

    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::put('/holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);

    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::put('/positions/{id}', [PositionController::class, 'update']);
    Route::delete('/positions/{id}', [PositionController::class, 'destroy']);

    Route::get('/post-numbers', [PostNumberController::class, 'index']);
    Route::post('/post-numbers', [PostNumberController::class, 'store']);
    Route::put('/post-numbers/{id}', [PostNumberController::class, 'update']);
    Route::delete('/post-numbers/{id}', [PostNumberController::class, 'destroy']);

    Route::get('/schedule-types/options', [ScheduleTypeController::class, 'options']);
    Route::get('/schedule-types', [ScheduleTypeController::class, 'index']);
    Route::post('/schedule-types', [ScheduleTypeController::class, 'store']);
    Route::put('/schedule-types/{id}', [ScheduleTypeController::class, 'update']);
    Route::delete('/schedule-types/{id}', [ScheduleTypeController::class, 'destroy']);

    Route::get('/schedules/options', [ScheduleController::class, 'options']);
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::put('/schedules/{id}', [ScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);

    Route::get('/work-schedules/options', [WorkScheduleController::class, 'options']);
    Route::get('/work-schedules', [WorkScheduleController::class, 'index']);
    Route::post('/work-schedules', [WorkScheduleController::class, 'store']);
    Route::put('/work-schedules/{id}', [WorkScheduleController::class, 'update']);
    Route::delete('/work-schedules/{id}', [WorkScheduleController::class, 'destroy']);

    Route::get('/work-time-rules', [WorkTimeRuleController::class, 'index']);
    Route::post('/work-time-rules', [WorkTimeRuleController::class, 'store']);
    Route::put('/work-time-rules/{id}', [WorkTimeRuleController::class, 'update']);
    Route::delete('/work-time-rules/{id}', [WorkTimeRuleController::class, 'destroy']);

    Route::get('/titles', [TitleController::class, 'index']);
    Route::post('/titles', [TitleController::class, 'store']);
    Route::put('/titles/{id}', [TitleController::class, 'update']);
    Route::delete('/titles/{id}', [TitleController::class, 'destroy']);

    Route::get('/reports/employee-list', [ReportController::class, 'employeeList']);
    Route::get('/reports/logbook', [ReportController::class, 'logbook']);
    Route::get('/reports/logbook-export', [ReportController::class, 'logbookExport']);

    Route::get('/dtr/options', [DtrController::class, 'options']);
    Route::post('/dtr/generate', [DtrController::class, 'generate']);
    Route::post('/dtr/pdf', [DtrController::class, 'pdf']);
    Route::get('/dtr/checkinout', [DtrController::class, 'checkinout']);
    Route::get('/dtr/workschedule', [DtrController::class, 'workschedule']);

    // Sync push — Sanctum-protected, called from local instance UI
    Route::post('/sync/push-employees', [SyncController::class, 'pushEmployees']);
    Route::post('/sync/push-offices', [SyncController::class, 'pushOffices']);
    Route::post('/sync/push-office-divisions', [SyncController::class, 'pushOfficeDivisions']);
    Route::post('/sync/push-work-schedules', [SyncController::class, 'pushWorkSchedules']);
    Route::post('/sync/push-attendances', [SyncController::class, 'pushAttendances']);
    Route::get('/sync/pending-counts', [SyncController::class, 'pendingCounts']);
    Route::get('/sync/logs', [SyncController::class, 'logs']);

    Route::get('/employees/options', [EmployeeController::class, 'options']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    Route::patch('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus']);
});
