<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestBreak;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        // 1. ログインユーザー取得
        $user = Auth::user();

        // 2. 現在日時取得
        $now = Carbon::now();

        // 3. 今日の勤怠があるか確認
        $attendance = $user->attendances()
            ->where('work_date', $now->toDateString())
            ->first();

        // 4. 「勤務外」のステータスを判断
        $status = $attendance
            ? $attendance->status()
            : '勤務外';

        // 5. ステータスに応じて表示するヘッダーを決定
        $headerNavType = $status === '退勤済'
            ? 'finished'
            : 'user';

        return view('attendance.index', compact(
            'now',
            'attendance',
            'status',
            'headerNavType'
        ));
    }

    public function clockIn()
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = $user->attendances()
            ->where('work_date', $now->toDateString())
            ->first();

        // 今日の勤怠がなければ作成する
        if ($attendance === null) {
            Attendance::create([
                'user_id' => $user->id,
                'work_date' => $now->toDateString(), // 例: "2026-06-28"
                'clock_in' => $now, // 例: "14:26:00"
            ]);
        }

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        $attendance->startBreak();

        return redirect()->route('attendance.index');
    }

    public function breakEnd()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        $attendance->endBreak();

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->first();

        $attendance->clockOut();

        return redirect()->route('attendance.index');
    }

    public function list(Request $request)
    {
        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m-d', $request->month . '-01')
            : now()->startOfMonth();

        $days = collect();

        for (
            $date = $currentMonth->copy()->startOfMonth();
            $date->lte($currentMonth->copy()->endOfMonth());
            $date->addDay()
        ) {
            $days->push($date->copy());
        }

        $attendances = Attendance::where('user_id', Auth::id())
            ->whereBetween('work_date', [
                $currentMonth->copy()->startOfMonth(),
                $currentMonth->copy()->endOfMonth(),
            ])
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->work_date->format('Y-m-d');
            });

        return view('attendance.list', compact('currentMonth', 'days', 'attendances'));
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('user', 'breaks');

        $pendingRequest = $attendance->attendanceRequests()
            ->where('status', 'pending')
            ->with('breaks')
            ->latest()
            ->first();

        return view('attendance.detail', compact(
            'attendance',
            'pendingRequest'
        ));
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        $validated = $request->validated();

        $requestedClockIn = Carbon::parse($attendance->clock_in)
            ->setTimeFromTimeString($validated['clock_in']);

        $requestedClockOut = Carbon::parse($attendance->clock_out)
            ->setTimeFromTimeString($validated['clock_out']);

        $attendanceRequest = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id(),
            'requested_clock_in' => $requestedClockIn,
            'requested_clock_out' => $requestedClockOut,
            'reason' => $validated['reason'],
        ]);

        foreach ($validated['breaks'] as $index => $break) {

            // 両方とも空白なら何もしない
            if (empty($break['break_start']) && empty($break['break_end'])) {
                continue;
            }

            if (isset($attendance->breaks[$index])) {

                $originalBreak = $attendance->breaks[$index];

                $breakStart = Carbon::parse($originalBreak->break_start)
                    ->setTimeFromTimeString($break['break_start']);

                $breakEnd = Carbon::parse($originalBreak->break_end)
                    ->setTimeFromTimeString($break['break_end']);
            } else {

                //新規追加休憩
                $date = Carbon::parse($attendance->clock_in);

                $breakStart = $date->copy()
                    ->setTimeFromTimeString($break['break_start']);

                $breakEnd = $date->copy()
                    ->setTimeFromTimeString($break['break_end']);
            }

            AttendanceRequestBreak::create([
                'attendance_request_id' => $attendanceRequest->id,
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
            ]);
        }

        return redirect()
            ->route('attendance.detail', $attendance);
    }
}
