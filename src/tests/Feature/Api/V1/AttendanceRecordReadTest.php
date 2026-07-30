<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceRequest;
use App\Models\User;

class AttendanceRecordReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // GET /api/v1/attendance-records で勤怠一覧が JSON で取得できることを確認するテスト
    public function test_attendance_records_can_be_retrieved(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    // GET /api/v1/attendance-records/{attendanceRecord} で勤怠詳細が JSON で取得できることを確認するテスト
    public function test_attendance_record_can_be_retrieved(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-28',
            'clock_in' => '2026-07-28 09:00:00',
            'clock_out' => '2026-07-28 18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-28 15:00:00',
            'break_end' => '2026-07-28 15:30:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-28 09:30:00',
            'requested_clock_out' => '2026-07-28 18:30:00',
            'reason' => 'テスト用修正申請',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user',
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks',
                'applications',
            ],
        ]);

        $response->assertJsonPath('data.id', $attendance->id);
        $response->assertJsonCount(1, 'data.breaks');
        $response->assertJsonCount(1, 'data.applications');
    }

    // 存在しないIDでは 404 と エラーJSON が返ることを確認するテスト
    public function test_returns_404_when_attendance_record_not_found(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
