<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // POST /api/v1/attendance-records で勤怠が作成されることを確認するテスト
    public function test_authenticated_user_can_store_attendance_record(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-25',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIテスト',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-25',
            'reason' => 'APIテスト',
        ]);

        $response->assertJsonFragment([
            'date' => '2026-07-25',
        ]);
    }

    // バリデーションエラー（勤怠日欠落）時に 422 と日本語エラーメッセージが返ることを確認するテスト
    public function test_date_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'clock_in' => '09:00:00',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date'])
            ->assertJsonFragment([
                '勤怠日は必須です。',
            ]);
    }

    // バリデーションエラー（出勤時刻欠落）時に 422 と日本語エラーメッセージが返ることを確認するテスト
    public function test_clock_in_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-25',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['clock_in'])
            ->assertJsonFragment([
                '出勤時刻は必須です。',
            ]);
    }

    // PUT /api/v1/attendance-records/{attendanceRecord} で勤怠が更新されることを確認するテスト
    public function test_authenticated_user_can_update_attendance_record()
    {
        // ユーザー作成
        /** @var User $user */
        $user = User::factory()->create();

        // 勤怠作成
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-02-05',
            'clock_in'  => '2026-02-05 09:00:00',
            'clock_out' => '2026-02-05 18:00:00',
            'reason'    => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/attendance-records/{$attendance->id}", [
                'clock_out' => '18:30:00',
                'comment'   => '残業対応',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_out' => '2026-02-05 18:30:00',
            'reason'    => '残業対応',
        ]);

        $response->assertJsonFragment([
            'clock_out' => '18:30:00',
            'comment'   => '残業対応',
        ]);
    }

    // 存在しないIDに対してPUT実行で 404 が返ることを確認するテスト
    public function test_returns_404_when_attendance_record_does_not_exist()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/attendance-records/99999', [
                'comment' => '更新テスト',
            ]);

        $response->assertNotFound();
    }

    // DELETE /api/v1/attendance-records/{attendanceRecord} で勤怠が削除されることを確認するテスト
    public function test_authenticated_user_can_delete_attendance_record()
    {
        // ユーザー作成
        /** @var User $user */
        $user = User::factory()->create();

        // 勤怠作成
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-02-05',
            'clock_in'  => '2026-02-05 09:00:00',
            'clock_out' => '2026-02-05 18:00:00',
            'reason'    => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    // 存在しないIDに対してDELETE実行で 404 が返ることを確認するテスト
    public function test_delete_nonexistent_attendance_returns_404()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}
