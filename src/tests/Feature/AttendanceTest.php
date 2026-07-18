<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceRequest;

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

    // 「勤務外」ステータスが表示されることを確認するテスト
    public function test_status_is_displayed_as_off_duty(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    // 「出勤中」ステータスが表示されることを確認するテスト
    public function test_status_is_displayed_as_working(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(9, 0),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    // 「休憩中」ステータスが表示されることを確認するテスト
    public function test_status_is_displayed_as_on_break(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(9, 0),
            'clock_out' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::today()->setTime(12, 0),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    // 「退勤済」ステータスが表示されることを確認するテスト
    public function test_status_is_displayed_as_finished(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    // 出勤ボタンが正しく機能することを確認するテスト
    public function test_user_can_clock_in(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('出勤');

        $this->post('/attendance/clock-in');

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    // 出勤は1日1回のみできることを確認するテスト
    public function test_clock_in_button_is_not_displayed_after_finishing_work(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
        $response->assertDontSee(
            'action="' . route('attendance.clock-in') . '"',
            false
        );

        Carbon::setTestNow();
    }

    // 出勤時刻が勤怠一覧画面で確認できることを確認するテスト
    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));

        $this->actingAs($user);

        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
        ]);

        $response = $this->get('/attendance/list');

        $response->assertSee('09:00');

        Carbon::setTestNow();
    }

    // 休憩ボタンが正しく機能することを確認するテスト
    public function test_break_start_button_works(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user);

        // 出勤中なので休憩入ボタンが表示される
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩入');

        // 休憩開始
        $this->post(route('attendance.break-start'));

        // ステータスが休憩中になる
        $this->get('/attendance')
            ->assertSee('休憩中');
    }

    // 休憩は1日に何回でもできることを確認するテスト
    public function test_break_can_be_taken_multiple_times(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.break-start'));
        $this->post(route('attendance.break-end'));

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩入');
    }

    // 休憩戻ボタンが正しく機能することを確認するテスト
    public function test_break_end_button_works(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.break-start'));

        // 休憩中なので休憩戻ボタンが表示される
        $this->get('/attendance')
            ->assertSee('休憩戻');

        $this->post(route('attendance.break-end'));

        // 出勤中へ戻る
        $this->get('/attendance')
            ->assertSee('出勤中');
    }

    // 休憩戻は1日に何回でもできることを確認するテスト
    public function test_break_end_can_be_done_multiple_times(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤（09:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));
        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        // 休憩開始（12:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 12, 0));
        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        // 休憩終了（13:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 13, 0));
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        // 再度休憩開始（15:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 15, 0));
        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        // 再び休憩中なので「休憩戻」ボタンが表示される
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    // 休憩時刻が勤怠一覧画面で確認できることを確認するテスト
    public function test_break_time_is_displayed_on_attendance_list(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤（09:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));
        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        // 休憩開始（12:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 12, 0));
        $this->post('/attendance/break-start');

        // 休憩終了（13:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 13, 0));
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        // 勤怠一覧画面で休憩時間が表示される
        $response = $this->get('/attendance/list');

        $response->assertSee('1:00');

        Carbon::setTestNow();
    }

    // 退勤ボタンが正しく機能することを確認するテスト
    public function test_clock_out_button_works(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤（09:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));
        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        // 出勤中なので退勤ボタンが表示される
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤');

        // 退勤（18:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 18, 0));
        $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        // ステータスが退勤済になる
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済');

        Carbon::setTestNow();
    }

    // 退勤時刻が勤怠一覧画面で確認できることを確認するテスト
    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤（09:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 9, 0));
        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        // 退勤（18:00）
        Carbon::setTestNow(Carbon::create(2026, 7, 12, 18, 0));
        $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        // 勤怠一覧画面で退勤時刻を確認
        $response = $this->get('/attendance/list');

        $response->assertStatus(200)
            ->assertSee('18:00');

        Carbon::setTestNow();
    }

    // 自分の勤怠情報が全て表示されることを確認するテスト
    public function test_all_attendance_records_are_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-01',
            'clock_in' => '2026-07-01 09:00:00',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-02',
            'clock_in' => '2026-07-02 09:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2026-07');

        $response
            ->assertStatus(200)
            ->assertSee('07/01')
            ->assertSee('07/02');
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示されることを確認するテスト
    public function test_current_month_is_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Carbon::setTestNow(Carbon::create(2026, 7, 12));

        $this->actingAs($user);

        $this->get('/attendance/list')
            ->assertStatus(200)
            ->assertSee('2026/07');

        Carbon::setTestNow();
    }

    // 「前月」を押下すると、前月が表示されることを確認するテスト
    public function test_previous_month_attendance_is_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/attendance/list?month=2026-06')
            ->assertStatus(200)
            ->assertSee('2026/06');
    }

    // 「翌月」を押下すると、翌月が表示されることを確認するテスト
    public function test_next_month_attendance_is_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/attendance/list?month=2026-08')
            ->assertStatus(200)
            ->assertSee('2026/08');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移することを確認するテスト
    public function test_can_navigate_to_attendance_detail(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
        ]);

        $this->actingAs($user);

        // 一覧画面を開く
        $response = $this->get('/attendance/list?month=2026-07');

        $response
            ->assertStatus(200)
            // 詳細リンクが表示されている
            ->assertSee('詳細')
            // 詳細リンクのURLが正しい
            ->assertSee(route('attendance.detail', $attendance), false);

        // 詳細ページへアクセス
        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200);
    }

    // 勤怠詳細画面の「名前」がログインユーザーの氏名になっていることを確認するテスト
    public function test_attendance_detail_displays_user_name(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('山田 太郎');
    }

    // 勤怠詳細画面の「日付」が選択した日付になっていることを確認するテスト
    public function test_attendance_detail_displays_work_date(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('2026年')
            ->assertSee('7月12日');
    }

    // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることを確認するテスト
    public function test_attendance_detail_displays_clock_in_and_out_time(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致していることを確認するテスト
    public function test_attendance_detail_displays_break_time(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
        ]);

        $attendance->breaks()->create([
            'break_start' => '2026-07-12 12:00:00',
            'break_end'   => '2026-07-12 13:00:00',
        ]);

        $this->actingAs($user);

        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200)
            ->assertSee('12:00')
            ->assertSee('13:00');
    }

    // 出勤時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_clock_in_is_after_clock_out(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('attendance.update', $attendance), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'breaks' => [],
            'reason' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_break_start_is_after_clock_out(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('attendance.update', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '19:00',
                    'break_end' => '13:00',
                ]
            ],
            'reason' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_break_end_is_after_clock_out(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('attendance.update', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '19:00',
                ]
            ],
            'reason' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考欄が未入力の場合、エラーメッセージが表示されることを確認するテスト
    public function test_validation_error_when_reason_is_empty(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('attendance.update', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [],
            'reason' => '',
        ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }

    // 修正申請処理が実行されることを確認するテスト
    public function test_attendance_correction_request_is_created(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var User $admin */
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        // 一般ユーザーで申請
        $this->actingAs($user);

        $this->patch(route('attendance.update', $attendance), [
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'breaks' => [],
            'reason' => '電車遅延',
        ]);

        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'reason' => '電車遅延',
        ]);

        // 管理者で確認
        $this->actingAs($admin);

        $response = $this->get(route('requests.index'));

        $response
            ->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee('電車遅延');

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)
            ->firstOrFail();

        $this->get(route('admin.requests.show', $attendanceRequest))
            ->assertStatus(200);
    }

    // 申請一覧の「承認待ち」に自分の申請が表示されることを確認するテスト
    public function test_pending_requests_are_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => $attendance->clock_in,
            'requested_clock_out' => $attendance->clock_out,
            'reason' => '電車遅延',
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $this->get('/stamp_correction_request/list')
            ->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee('電車遅延');
    }

    // 申請一覧の「承認済み」に承認済み申請が表示されることを確認するテスト
    public function test_approved_requests_are_displayed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => $attendance->clock_in,
            'requested_clock_out' => $attendance->clock_out,
            'reason' => '電車遅延',
            'status' => 'approved',
        ]);

        $this->actingAs($user);

        $this->get('/stamp_correction_request/list?status=approved')
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee('電車遅延');
    }

    // 申請一覧の詳細から勤怠詳細画面へ遷移できることを確認するテスト
    public function test_can_navigate_to_attendance_detail_from_request_list(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-07-12',
            'clock_in' => '2026-07-12 09:00:00',
            'clock_out' => '2026-07-12 18:00:00',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in' => $attendance->clock_in,
            'requested_clock_out' => $attendance->clock_out,
            'reason' => '電車遅延',
        ]);

        $this->actingAs($user);

        $response = $this->get('/stamp_correction_request/list');

        $response
            ->assertSee('詳細')
            ->assertSee(route('attendance.detail', $attendance), false);

        $this->get(route('attendance.detail', $attendance))
            ->assertStatus(200);
    }
}
