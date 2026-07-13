<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//一般ユーザー認証ミドルウェア
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])
        ->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])
        ->name('attendance.break-end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');
    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'show'])
        ->name('attendance.detail');
    Route::patch('/attendance/detail/{attendance}', [AttendanceController::class, 'update'])
        ->name('attendance.update');
    Route::get('/attendance/report', [ReportController::class, 'index'])
        ->name('attendance.report');
});

//共通の認証ミドルウェア
Route::middleware(['auth'])->group(function () {
    Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'index'])
        ->name('requests.index');
});

//管理者ユーザー認証ミドルウェア
Route::middleware(['admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.list');
    Route::get('/admin/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.detail');
    Route::patch('admin/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])
        ->name('admin.staff.list');
    Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staffAttendance'])
        ->name('admin.attendance.staff');
    Route::get('/admin/attendance/staff/{user}/csv', [AdminAttendanceController::class, 'exportCsv'])
        ->name('admin.attendance.staff.csv');
    Route::get('/stamp_correction_request/approve/{attendanceRequest}', [AdminRequestController::class, 'show'])
        ->name('admin.requests.show');
    Route::patch('/stamp_correction_request/approve/{attendanceRequest}', [AdminRequestController::class, 'approve'])
        ->name('admin.requests.approve');
});

// ログアウト後のリダイレクト先が初期状態で'/'となっているため、トップページ（ / ）のルートをログイン画面に指定する
Route::get('/', function () {
    return redirect('/login');
});

// 管理者専用のログイン
Route::get('/admin/login', [AdminAuthController::class, 'create']);
Route::post('/admin/login', [AdminAuthController::class, 'store']);
