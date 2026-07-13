<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Services\AttendanceReportService;
use Illuminate\Support\Facades\Auth;

/**
 * マイ勤怠レポート画面を表示するコントローラー
 */
class ReportController extends Controller
{
    /**
     * マイ勤怠レポート画面を表示する。
     *
     * @param AttendanceReportService $service
     * @return View
     */
    public function index(AttendanceReportService $service): View
    {
        $report = $service->generate(Auth::id());
        return view('attendance.report', [
            'report' => $report,
            'headerNavType' => 'report',
        ]);
    }
}
