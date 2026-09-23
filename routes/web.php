<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminStaffAttendanceController;
use App\Http\Controllers\AdminCorrectionRequestController;
use App\Http\Controllers\CsvExportController;


Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| 一般会員
|--------------------------------------------------------------------------
*/
//一般会員登録
Route::get('/register', function () {
    return view('user.register');
});
Route::post('/register', [RegisterController::class, 'store']);

//ログイン
Route::get('/login', [LoginController::class, 'create'])
    ->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login.store');

Route::middleware(['auth', 'verified'])->group(function () {
    //勤怠登録
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::post('/attendance', [AttendanceController::class, 'store'])
        ->name('attendance.store');

    //勤怠一覧
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])
        ->name('attendance.list');

    //レポート
    Route::get('/attendance/report', [AttendanceReportController::class, 'index'])
        ->name('attendance.report');

    //勤怠詳細
    Route::get('/attendance/{id}', function ($id) {
        if (Auth::user()->is_admin) {
            return redirect()->route('admin.attendance.show', [
                'id' => $id
            ]);
        }

        return app(AttendanceDetailController::class)->show($id);
    })->name('attendance.detail');
    /*
    Route::post('/attendance/{id}', function (Request $request, $id) {

        if (Auth::user()->is_admin) {
            return app(AdminAttendanceController::class)->update($request, $id);
        }

        return app(AttendanceDetailController::class)->update($request, $id);

    })->name('attendance.detail.update');
    */

    Route::post('/attendance/{id}', [AttendanceDetailController::class, 'update'])
        ->name('attendance.detail.update');

    //申請
    Route::get('/applications', [ApplicationController::class, 'index'])
        ->name('applications.index');

    Route::get('/application/{id}', [ApplicationController::class, 'show'])
        ->name('applications.show');

});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');




/*
|--------------------------------------------------------------------------
| 管理者ログイン
|--------------------------------------------------------------------------
*/

// 管理者ログイン画面
Route::get('/admin/login', [AdminLoginController::class, 'create'])
    ->name('admin.login');

// 管理者ログイン処理
Route::post('/admin/login', [AdminLoginController::class, 'store'])
    ->name('admin.login.store');


/*
|--------------------------------------------------------------------------
| 管理者画面
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    // 管理者勤怠一覧
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');

    // 管理者勤怠詳細
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');

    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    // スタッフ一覧
    Route::get('/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.index');

    // スタッフ別月次勤怠
    Route::get('/attendance/staff/{userId}', [AdminStaffAttendanceController::class, 'index'])
        ->name('admin.staff.attendance.index');

    // 管理者ログアウト
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->name('admin.logout');

    // 修正申請一覧
    Route::get(
        '/stamp_correction_request/list',
        [AdminCorrectionRequestController::class, 'index']
    )->name('admin.correction-requests.index');

    // 修正申請詳細
    Route::get(
        '/stamp_correction_request/{id}',
        [AdminCorrectionRequestController::class, 'show']
    )->name('admin.correction-requests.show');

    // 修正申請承認
    Route::post(
        '/stamp_correction_request/approve/{id}',
        [AdminCorrectionRequestController::class, 'approve']
    )->name('admin.correction-requests.approve');
});


Route::middleware('auth')->group(function () {

    // 一般ユーザー・管理者共通の申請一覧
    Route::get('/stamp_correction_request/list', function () {

        if (Auth::user()->is_admin) {
            return app(AdminCorrectionRequestController::class)->index();
        }

        return app(ApplicationController::class)->index();
    });

    // 管理者用：修正申請詳細
    Route::get(
        '/stamp_correction_request/approve/{id}',
        [AdminCorrectionRequestController::class, 'show']
    )->middleware('admin');

    // 管理者用：修正申請承認
    Route::post(
        '/stamp_correction_request/approve/{id}',
        [AdminCorrectionRequestController::class, 'approve']
    )->middleware('admin');
});

Route::middleware(['auth', 'admin'])->group(function () {

    Route::post('/export', [CsvExportController::class, 'export'])
        ->name('csv.export');

});

/*
|--------------------------------------------------------------------------
| メール認証
|--------------------------------------------------------------------------
*/

// メール認証案内画面
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// メール認証リンク
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

// 認証メール再送信
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', '認証メールを再送信しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');