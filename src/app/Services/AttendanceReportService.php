<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceReportService
{
    /**
     * マイ勤怠レポートデータを取得する。
     *
     * @param int $userId
     * @return array
     */
    public function generate(int $userId): array
    {
        $startDate = Carbon::now()
            ->subMonths(5)
            ->startOfMonth();

        $endDate = Carbon::now()
            ->endOfMonth();

        $attendances = Attendance::query()
            ->with('breaks')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [
                $startDate,
                $endDate,
            ])
            ->orderBy('work_date')
            ->get();

        $totalWorkMinutes = $attendances->sum(function ($attendance) {

            return $this->calculateWorkMinutes($attendance);
        });

        $totalOvertimeMinutes = $attendances->sum(function ($attendance) {

            return $this->calculateOvertimeMinutes($attendance);
        });

        $averageWorkMinutes = $attendances->avg(function ($attendance) {

            return $this->calculateWorkMinutes($attendance);
        });

        $averageWorkMinutes = (int) round($averageWorkMinutes);

        $monthlyAttendances = $attendances->groupBy(function ($attendance) {

            return $attendance->work_date->format('Y-m');
        });

        $monthlyReports = $monthlyAttendances->map(function ($attendances, $month) {

            $monthlyWorkMinutes = $attendances->sum(function ($attendance) {

                return $this->calculateWorkMinutes($attendance);
            });

            $monthlyOvertimeMinutes = $attendances->sum(function ($attendance) {

                return $this->calculateOvertimeMinutes($attendance);
            });

            return [
                'month' => $month,
                'workMinutes' => $monthlyWorkMinutes,
                'overtimeMinutes' => $monthlyOvertimeMinutes,
                'workTime' => $this->formatMinutes($monthlyWorkMinutes),
                'overtimeTime' => $this->formatMinutes($monthlyOvertimeMinutes),
            ];
        });

        $months = collect(range(5, 0))
            ->map(function ($i) {

                return now()
                    ->subMonths($i)
                    ->format('Y-m');
            });

        $monthlyTrend = $months->map(function ($month) use ($monthlyReports) {

            $report = $monthlyReports->get($month);

            if ($report === null) {

                return [
                    'month' => $month,
                    'workMinutes' => 0,
                    'overtimeMinutes' => 0,
                    'workTime' => $this->formatMinutes(0),
                    'overtimeTime' => $this->formatMinutes(0),
                ];
            }

            return $report;
        });

        $currentMonthAttendances = $attendances->filter(function ($attendance) {

            return $attendance->work_date->between(
                now()->startOfMonth(),
                now()->endOfMonth()
            );
        });

        $lateCount = $currentMonthAttendances
            ->filter(function ($attendance) {

                return Carbon::parse($attendance->clock_in)
                    ->gt(
                        Carbon::parse($attendance->work_date)
                            ->setTime(9, 0)
                    );
            })
            ->count();

        $earlyLeaveCount = $currentMonthAttendances
            ->filter(function ($attendance) {

                return Carbon::parse($attendance->clock_out)
                    ->lt(
                        Carbon::parse($attendance->work_date)
                            ->setTime(18, 0)
                    );
            })
            ->count();

        $longWorkCount = $currentMonthAttendances
            ->filter(function ($attendance) {

                return $this->calculateWorkMinutes($attendance) > 10 * 60;
            })
            ->count();

        return [
            'totalWorkTime' => $this->formatMinutes($totalWorkMinutes),
            'totalOvertimeTime' => $this->formatMinutes($totalOvertimeMinutes),
            'averageWorkTime' => $this->formatMinutes($averageWorkMinutes),
            'monthlyTrend' => $monthlyTrend,
            'lateCount' => $lateCount,
            'earlyLeaveCount' => $earlyLeaveCount,
            'longWorkCount' => $longWorkCount,
        ];
    }

    /**
     * 1日の実労働時間（分）を計算する。
     *
     * @param Attendance $attendance
     * @return int
     */
    private function calculateWorkMinutes(Attendance $attendance): int
    {
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);
        $workMinutes = $clockOut->diffInMinutes($clockIn);
        $breakMinutes = $attendance->breaks->sum(function ($break) {

            return Carbon::parse($break->break_end)
                ->diffInMinutes(
                    Carbon::parse($break->break_start)
                );
        });

        return $workMinutes - $breakMinutes;
    }

    /**
     * 勤怠1件の残業時間（分）を計算する
     *
     * 8時間（480分）を超えた労働時間のみを残業時間として返す。
     *
     * @param Attendance $attendance 勤怠情報
     * @return int 残業時間（分）
     */
    private function calculateOvertimeMinutes(Attendance $attendance): int
    {
        $workMinutes = $this->calculateWorkMinutes($attendance);

        $standardWorkMinutes = 8 * 60;

        if ($workMinutes > $standardWorkMinutes) {
            return $workMinutes - $standardWorkMinutes;
        }

        return 0;
    }

    /**
     * 分を「○h ○m」形式へ変換する
     *
     * @param int $minutes
     * @return string
     */
    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%dh %dm', $hours, $remainingMinutes);
    }
}
