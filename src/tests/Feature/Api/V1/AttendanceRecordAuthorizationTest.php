<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AttendanceRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // 認証なしでPOST /api/v1/attendance-records 実行で 401 が返ることを確認するテスト
    public function test_guest_cannot_store_attendance_record(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-07-25',
            'clock_in' => '09:00:00',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    // 認証なしでPUT /api/v1/attendance-records 実行で 401 が返ることを確認するテスト
    public function test_guest_cannot_update_attendance_record()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => null,
        ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_out' => '18:00:00',
            ]
        );

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    // 認証なしでDELETE /api/v1/attendance-records 実行で 401 が返ることを確認するテスト
    public function test_guest_cannot_delete_attendance_record()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => '2026-07-29 18:00:00',
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    // 認証済みユーザーは自分の勤怠を更新することができることを確認するテスト
    public function test_authenticated_user_can_update_own_attendance_record()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => null,
        ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_out' => '18:00:00',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out' => '2026-07-29 18:00:00',
        ]);
    }


    public function test_authenticated_user_can_delete_own_attendance_record()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => '2026-07-29 18:00:00',
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    // 他ユーザーの勤怠を更新しようとすると 403 が返ることを確認するテスト
    public function test_user_cannot_update_other_users_attendance_record()
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => '2026-07-29 18:00:00',
        ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_out' => '19:00:00',
            ]
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }

    // 他ユーザーの勤怠を削除しようとすると 403 が返ることを確認するテスト
    public function test_user_cannot_delete_other_users_attendance_record()
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-07-29',
            'clock_in' => '2026-07-29 09:00:00',
            'clock_out' => '2026-07-29 18:00:00',
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }
}
