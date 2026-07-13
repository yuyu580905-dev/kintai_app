<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    use RefreshDatabase;

    // 勤怠打刻画面で現在の日時情報がUIと同じ形式で出力されていることを確認するテスト
    public function test_current_date_and_time_are_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 7, 12, 9, 30)
        );

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026年7月12日(日)');
        $response->assertSee('09:30');

        Carbon::setTestNow();
    }

    // 勤務外の場合、画面上に表示されているステータスが「勤務外」となることを確認するテスト
    public function test_status_is_displayed_as_off_duty(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('勤務外');
    }
}
