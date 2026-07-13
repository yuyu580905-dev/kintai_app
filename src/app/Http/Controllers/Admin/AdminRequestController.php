<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\DB;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $requests = AttendanceRequest::with([
            'attendance.user',
        ])
            ->when($status === 'pending', function ($query) {
                $query->where('attendance_requests.status', AttendanceRequest::STATUS_PENDING);
            })
            ->when($status === 'approved', function ($query) {
                $query->where('attendance_requests.status', AttendanceRequest::STATUS_APPROVED);
            })
            ->orderByWorkDate()
            ->get();

        return view('admin.request-list', compact(
            'requests',
            'status'
        ));
    }

    public function show($attendanceRequestId)
    {
        $attendanceRequest = AttendanceRequest::with([
            'user',
            'attendance',
            'breaks',
        ])->findOrFail($attendanceRequestId);

        return view('admin.request-approve', compact('attendanceRequest'));
    }

    public function approve($attendanceRequestId)
    {
        $attendanceRequest = AttendanceRequest::with([
            'attendance',
            'breaks',
        ])->findOrFail($attendanceRequestId);

        DB::transaction(function () use ($attendanceRequest) {

            $attendance = $attendanceRequest->attendance;

            //勤怠情報更新
            $attendance->update([
                'clock_in'  => $attendanceRequest->requested_clock_in,
                'clock_out' => $attendanceRequest->requested_clock_out,
                'reason' => $attendanceRequest->reason,
            ]);

            //既存の休憩を削除
            $attendance->breaks()->delete();

            //修正申請の休憩を反映
            foreach ($attendanceRequest->breaks as $requestBreak) {

                $attendance->breaks()->create([
                    'break_start' => $requestBreak->break_start,
                    'break_end'   => $requestBreak->break_end,
                ]);
            }

            //承認済みに変更
            $attendanceRequest->update([
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.requests.show', $attendanceRequest);
    }
}
