<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OfficeController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

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

Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'store']);
Route::put('/roles/{id}', [RoleController::class, 'update']);
Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

Route::get('/offices', [OfficeController::class, 'index']);
Route::post('/offices', [OfficeController::class, 'store']);
Route::put('/offices/{id}', [OfficeController::class, 'update']);
Route::delete('/offices/{id}', [OfficeController::class, 'destroy']);

Route::get('/employees/options', [EmployeeController::class, 'options']);
Route::get('/employees', [EmployeeController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
