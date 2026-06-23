<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BiometricController;

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
