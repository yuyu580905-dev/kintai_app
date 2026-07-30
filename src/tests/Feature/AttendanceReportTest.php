<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // ゲストはレポートページにアクセスできないことを確認するテスト
    public function test_guest_cannot_access_attendance_report(): void
    {
        $response = $this->get(route('attendance.report'));

        $response->assertRedirect('/login');
    }

    // 認証ユーザーの統計情報が正しく計算されることを確認するテスト
    public function test_report_statistics_are_calculated_correctly(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // 必ず同じ月になるように日付を固定
        $day1 = now()->startOfMonth()->addDays(10);
        $day2 = $day1->copy()->addDay();

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $day1,
            'clock_in' => $day1->copy()->setTime(9, 0),
            'clock_out' => $day1->copy()->setTime(18, 0),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance1->id,
            'break_start' => $day1->copy()->setTime(12, 0),
            'break_end' => $day1->copy()->setTime(13, 0),
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $day2,
            'clock_in' => $day2->copy()->setTime(10, 0),
            'clock_out' => $day2->copy()->setTime(20, 0),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance2->id,
            'break_start' => $day2->copy()->setTime(12, 0),
            'break_end' => $day2->copy()->setTime(13, 0),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('attendance.report'));

        $response->assertStatus(200);

        $response->assertViewHas('report', function ($report) use ($day1) {

            return
                $report['totalWorkTime'] === '17h 0m'
                && $report['totalOvertimeTime'] === '1h 0m'
                && $report['averageWorkTime'] === '8h 30m'
                && count($report['monthlyTrend']) === 6
                && collect($report['monthlyTrend'])->contains(function ($month) use ($day1) {
                    return $month['month'] === $day1->format('Y-m')
                        && $month['workTime'] === '17h 0m'
                        && $month['overtimeTime'] === '1h 0m';
                })
                && $report['lateCount'] === 1
                && $report['earlyLeaveCount'] === 0
                && $report['longWorkCount'] === 0;
        });
    }

    // 勤怠記録がないユーザーで安全に処理されることを確認するテスト
    public function test_report_is_safe_when_user_has_no_attendance_records(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('attendance.report'));

        $response->assertStatus(200);

        $response->assertViewHas('report', function ($report) {

            return
                $report['totalWorkTime'] === '0h 0m'
                && $report['totalOvertimeTime'] === '0h 0m'
                && $report['averageWorkTime'] === '0h 0m'
                && count($report['monthlyTrend']) === 6
                && collect($report['monthlyTrend'])->every(function ($month) {
                    return $month['workTime'] === '0h 0m'
                        && $month['overtimeTime'] === '0h 0m';
                })
                && $report['lateCount'] === 0
                && $report['earlyLeaveCount'] === 0
                && $report['longWorkCount'] === 0;
        });
    }
}
