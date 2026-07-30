<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestBreak;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // その日になされた全ユーザーの勤怠情報が確認できることを確認するテスト
    public function test_all_users_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-07-16');

        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user1 */
        $user1 = User::factory()->create([
            'name' => '山田太郎',
        ]);

        /** @var User $user2 */
        $user2 = User::factory()->create([
            'name' => '佐藤花子',
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 10:00:00',
            'clock_out' => '2026-07-16 19:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '2026-07-16 15:00:00',
            'break_end' => '2026-07-16 15:30:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.list'));

        $response
            ->assertStatus(200)

            // ユーザー名
            ->assertSee('山田太郎')
            ->assertSee('佐藤花子')

            // 出勤・退勤
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('10:00')
            ->assertSee('19:00')

            // 休憩時間
            ->assertSee($attendance1->formattedBreakTime())
            ->assertSee($attendance2->formattedBreakTime())

            // 勤務時間
            ->assertSee($attendance1->formattedWorkingTime())
            ->assertSee($attendance2->formattedWorkingTime());

        Carbon::setTestNow();
    }

    // 勤怠一覧画面へ遷移した際に、現在の日付が表示されていることを確認するテスト
    public function test_current_date_is_displayed(): void
    {
        Carbon::setTestNow('2026-07-16');

        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.attendance.list'))
            ->assertStatus(200)
            ->assertSee('2026/07/16');

        Carbon::setTestNow();
    }

    // 「前日」を押下すると、前の日の勤怠情報が表示されることを確認するテスト
    public function test_previous_day_attendance_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-15',
            'clock_in' => '2026-07-15 09:00:00',
            'clock_out' => '2026-07-15 18:00:00',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.attendance.list', [
            'date' => '2026-07-15',
        ]))
            ->assertStatus(200)
            ->assertSee('2026/07/15')
            ->assertSee('山田太郎');
    }

    // 「翌日」を押下すると、次の日の勤怠情報が表示されることを確認するテスト
    public function test_next_day_attendance_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-17',
            'clock_in' => '2026-07-17 09:00:00',
            'clock_out' => '2026-07-17 18:00:00',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.attendance.list', [
            'date' => '2026-07-17',
        ]))
            ->assertStatus(200)
            ->assertSee('2026/07/17')
            ->assertSee('山田太郎');
    }

    // 出勤時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_clock_in_is_after_clock_out(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('admin.attendance.detail', $attendance))
            ->patch(route('admin.attendance.update', $attendance), [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'reason' => '修正',
            ]);

        $response
            ->assertRedirect(route('admin.attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    // 休憩開始時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_break_start_is_after_clock_out(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('admin.attendance.detail', $attendance))
            ->patch(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => '13:00',
                    ],
                ],
                'reason' => '修正',
            ]);

        $response
            ->assertRedirect(route('admin.attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'breaks.0.break_start' => '休憩時間が不適切な値です',
            ]);
    }

    // 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_break_end_is_after_clock_out(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('admin.attendance.detail', $attendance))
            ->patch(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '19:00',
                    ],
                ],
                'reason' => '修正',
            ]);

        $response
            ->assertRedirect(route('admin.attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    // 備考欄が未入力の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_reason_is_empty(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('admin.attendance.detail', $attendance))
            ->patch(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [],
                'reason' => '',
            ]);

        $response
            ->assertRedirect(route('admin.attendance.detail', $attendance))
            ->assertSessionHasErrors([
                'reason' => '備考を記入してください',
            ]);
    }

    // 全一般ユーザーの氏名・メールアドレスが表示されることを確認するテスト
    public function test_admin_can_view_all_general_users(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user1 */
        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        /** @var User $user2 */
        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.staff.list'))
            ->assertStatus(200)
            ->assertSee($user1->name)
            ->assertSee($user1->email)
            ->assertSee($user2->name)
            ->assertSee($user2->email);
    }

    // 選択したスタッフの勤怠一覧が表示されることを確認するテスト
    public function test_staff_attendance_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.attendance.staff', [
            'user' => $user,
            'month' => '2026-07',
        ]))
            ->assertStatus(200)
            ->assertSee('山田太郎')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee($attendance->formattedBreakTime())
            ->assertSee($attendance->formattedWorkingTime());
    }

    // 「前月」を押下すると、表示月の前月の勤怠情報が表示されることを確認するテスト
    public function test_previous_month_attendance_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($admin);

        $this->get(route('admin.attendance.staff', [
            'user' => $user,
            'month' => '2026-06',
        ]))
            ->assertStatus(200)
            ->assertSee('2026/06');
    }

    // 「翌月」を押下すると、表示月の翌月の勤怠情報が表示されることを確認するテスト
    public function test_next_month_attendance_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($admin);

        $this->get(route('admin.attendance.staff', [
            'user' => $user,
            'month' => '2026-08',
        ]))
            ->assertStatus(200)
            ->assertSee('2026/08');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移することを確認するテスト
    public function test_admin_can_open_attendance_detail(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $this->actingAs($admin);

        // スタッフ別勤怠一覧に「詳細」が表示されている
        $this->get(route('admin.attendance.staff', [
            'user' => $user,
            'month' => '2026-07',
        ]))
            ->assertStatus(200)
            ->assertSee('詳細');

        // 詳細画面へ遷移できる
        $this->get(route('admin.attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('山田太郎')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    // 承認待ちの修正申請が全て表示されていることを確認するテスト
    public function test_pending_requests_are_displayed_for_admin(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-16 09:30:00',
            'requested_clock_out' => '2026-07-16 18:30:00',
            'reason' => '電車遅延',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        $this->get(route('requests.index'))
            ->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee($user->name)
            ->assertSee('電車遅延');
    }

    // 承認済みの修正申請が全て表示されていることを確認するテスト
    public function test_approved_requests_are_displayed_for_admin(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-16 09:30:00',
            'requested_clock_out' => '2026-07-16 18:30:00',
            'reason' => '病院受診',
            'status' => AttendanceRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($admin);

        $this->get(route('requests.index', [
            'status' => 'approved',
        ]))
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee($user->name)
            ->assertSee('病院受診');
    }

    // 修正申請の詳細内容が正しく表示されていることを確認するテスト
    public function test_request_detail_is_displayed(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-16 09:30:00',
            'requested_clock_out' => '2026-07-16 18:30:00',
            'reason' => '電車遅延',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        AttendanceRequestBreak::create([
            'attendance_request_id' => $request->id,
            'break_start' => '2026-07-16 12:00:00',
            'break_end' => '2026-07-16 13:00:00',
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.requests.show', $request))
            ->assertStatus(200)
            ->assertSee('山田太郎')
            ->assertSee('09:30')
            ->assertSee('18:30')
            ->assertSee('12:00')
            ->assertSee('13:00')
            ->assertSee('電車遅延');
    }

    // 修正申請の承認処理が正しく行われることを確認するテスト
    public function test_request_is_approved_and_attendance_is_updated(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-16',
            'clock_in' => '2026-07-16 09:00:00',
            'clock_out' => '2026-07-16 18:00:00',
        ]);

        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => '2026-07-16 09:30:00',
            'requested_clock_out' => '2026-07-16 18:30:00',
            'reason' => '電車遅延',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        AttendanceRequestBreak::create([
            'attendance_request_id' => $request->id,
            'break_start' => '2026-07-16 12:30:00',
            'break_end' => '2026-07-16 13:30:00',
        ]);

        $this->actingAs($admin);

        $this->patch(route('admin.requests.approve', $request))
            ->assertRedirect(route('admin.requests.show', $request));

        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => AttendanceRequest::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'reason' => '電車遅延',
        ]);

        $attendance->refresh();

        $this->assertEquals('09:30', $attendance->clock_in->format('H:i'));
        $this->assertEquals('18:30', $attendance->clock_out->format('H:i'));

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-07-16 12:30:00',
            'break_end' => '2026-07-16 13:30:00',
        ]);
    }
}
