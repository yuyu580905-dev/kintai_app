<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
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
}
