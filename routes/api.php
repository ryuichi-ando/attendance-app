<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AttendanceRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // 勤怠一覧取得
    Route::get(
        '/attendance-records',
        [AttendanceRecordController::class, 'index']
    );

    // 勤怠詳細取得
    Route::get(
        '/attendance-records/{attendanceRecord}',
        [AttendanceRecordController::class, 'show']
    );

    // 勤怠作成
    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/attendance-records',
            [AttendanceRecordController::class, 'store']
        );

        Route::put(
            '/attendance-records/{attendanceRecord}',
            [AttendanceRecordController::class, 'update']
        );

        Route::delete(
            '/attendance-records/{attendanceRecord}',
            [AttendanceRecordController::class, 'destroy']
        );
    });
});
