<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceUpdateRequest;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $currentDate = Carbon::parse(
            $request->date ?? today()
        );

        $attendances = Attendance::with([
            'user',
            'breaks',
        ])
            ->whereDate('work_date', $currentDate)
            ->get();

        return view(
            'admin.attendance-list',
            compact('currentDate', 'attendances')
        );
    }

    public function show(Attendance $attendance)
    {
        $attendance->load([
            'user',
            'breaks',
            'attendanceRequests',
        ]);

        $pendingRequest = $attendance->attendanceRequests()
            ->where('status', 'pending')
            ->with('breaks')
            ->latest()
            ->first();

        return view('admin.attendance-detail', compact(
            'attendance',
            'pendingRequest'
        ));
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        $validated = $request->validated();

        $clockIn = Carbon::parse($attendance->clock_in)
            ->setTimeFromTimeString($validated['clock_in']);

        $clockOut = Carbon::parse($attendance->clock_out)
            ->setTimeFromTimeString($validated['clock_out']);

        // 勤怠更新
        $attendance->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'reason' => $validated['reason'],
        ]);

        // 休憩更新
        foreach ($validated['breaks'] as $index => $break) {

            // 両方とも空なら何もしない
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            if (isset($attendance->breaks[$index])) {

                // 既存休憩
                $originalBreak = $attendance->breaks[$index];

                $breakStart = Carbon::parse($originalBreak->break_start)
                    ->setTimeFromTimeString($break['break_start']);

                $breakEnd = Carbon::parse($originalBreak->break_end)
                    ->setTimeFromTimeString($break['break_end']);

                $originalBreak->update([
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);
            } else {

                // 新規休憩
                $date = Carbon::parse($attendance->clock_in);

                $breakStart = $date->copy()
                    ->setTimeFromTimeString($break['break_start']);

                $breakEnd = $date->copy()
                    ->setTimeFromTimeString($break['break_end']);

                $attendance->breaks()->create([
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);
            }
        }

        return redirect()
            ->route('admin.attendance.detail', $attendance);
    }

    public function staffAttendance(User $user)
    {
        $month = request()->filled('month')
            ? Carbon::createFromFormat('Y-m-d', request('month') . '-01')
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $attendances = $this->getMonthlyAttendances($user, $month)
            ->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        $days = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $days[] = $current->copy();
            $current->addDay();
        }

        return view('admin.staff-attendance-list', compact(
            'user',
            'attendances',
            'days',
            'month'
        ));
    }

    public function exportCsv(User $user)
    {
        $month = request()->filled('month')
            ? Carbon::createFromFormat('Y-m-d', request('month') . '-01')
            : now()->startOfMonth();

        $attendances = $this->getMonthlyAttendances($user, $month);

        return response()->streamDownload(
            function () use ($attendances) {

                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // ヘッダー
                fputcsv($handle, [
                    '日付',
                    '出勤',
                    '退勤',
                    '休憩',
                    '合計',
                ]);

                foreach ($attendances as $attendance) {
                    fputcsv($handle, [
                        $attendance->work_date->format('Y/m/d'),
                        optional($attendance->clock_in)->format('H:i'),
                        optional($attendance->clock_out)->format('H:i'),
                        $attendance->formattedBreakTime(),
                        $attendance->formattedWorkingTime(),
                    ]);
                }

                fclose($handle);
            },
            $user->name . '_' . $month->format('Y-m') . '.csv'
        );
    }

    // 指定ユーザー・指定月の勤怠一覧を取得
    private function getMonthlyAttendances(User $user, Carbon $month)
    {
        return Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween(
                'work_date',
                [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ]
            )
            ->orderBy('work_date')
            ->get();
    }
}
