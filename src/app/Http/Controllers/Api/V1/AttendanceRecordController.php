<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceRecordController extends Controller
{
    public function index(IndexAttendanceRecordRequest $request)
    {
        $perPage = $request->input('per_page', 20);

        $attendances = Attendance::with([
            'user',
            'breaks',
        ])

            ->when(
                $request->input('user_id'),
                function ($query, $userId) {
                    $query->where('user_id', $userId);
                }
            )

            ->when(
                $request->input('date'),
                function ($query, $date) {
                    $query->whereDate('work_date', $date);
                }
            )

            ->when(
                $request->input('month'),
                function ($query, $month) {
                    $targetMonth = Carbon::parse($month);
                    $query->whereYear('work_date', $targetMonth->year);
                    $query->whereMonth('work_date', $targetMonth->month);
                }
            )
            ->latest('work_date')->paginate($perPage);

        return AttendanceRecordResource::collection($attendances);
    }

    public function show(Attendance $attendanceRecord)
    {
        $attendanceRecord->load([
            'user',
            'breaks',
            'attendanceRequests',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();

        $attendance = $request->user()->attendances()->create([
            'work_date' => $validated['date'],
            'clock_in' => $validated['date'] . ' ' . $validated['clock_in'],
            'clock_out' => isset($validated['clock_out'])
                ? $validated['date'] . ' ' . $validated['clock_out']
                : null,
            'reason' => $validated['comment'] ?? null,
        ]);

        $attendance->load([
            'user',
            'breaks',
        ]);

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateAttendanceRecordRequest $request,
        Attendance $attendanceRecord
    ) {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();

        // 更新後に使用する日付（未送信なら既存の日付）
        $date = $validated['date']
            ?? $attendanceRecord->work_date->format('Y-m-d');

        $updateData = [];

        // 勤務日
        if (array_key_exists('date', $validated)) {
            $updateData['work_date'] = $date;
        }

        // 備考
        if (array_key_exists('comment', $validated)) {
            $updateData['reason'] = $validated['comment'];
        }

        // 出勤時刻
        if (array_key_exists('clock_in', $validated)) {
            $updateData['clock_in'] = $date . ' ' . $validated['clock_in'];
        }

        // 退勤時刻
        if (array_key_exists('clock_out', $validated)) {
            $updateData['clock_out'] = $validated['clock_out']
                ? $date . ' ' . $validated['clock_out']
                : null;
        }

        // 日付だけ変更された場合は既存時刻の日付も合わせる
        if (array_key_exists('date', $validated)) {

            if (
                ! array_key_exists('clock_in', $validated) &&
                $attendanceRecord->clock_in
            ) {
                $updateData['clock_in'] =
                    $date . ' ' . $attendanceRecord->clock_in->format('H:i:s');
            }

            if (
                ! array_key_exists('clock_out', $validated) &&
                $attendanceRecord->clock_out
            ) {
                $updateData['clock_out'] =
                    $date . ' ' . $attendanceRecord->clock_out->format('H:i:s');
            }
        }

        $attendanceRecord->update($updateData);

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
