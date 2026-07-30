<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();
        $user3 = User::where('email', 'user3@example.com')->first();

        $this->createUser1Attendances($user1);
        $this->createUser2Attendances($user2);
        $this->createUser3Attendances($user3);
    }

    private function createUser1Attendances(User $user): void
    {
        $this->createPastFiveMonths($user);
        $this->createCurrentMonth($user);
    }

    private function createUser2Attendances(User $user): void
    {
        for ($month = 2; $month > 0; $month--) {

            $patterns = array_merge(
                array_fill(0, 12, ['09:00', '18:00']),
                array_fill(0, 4, ['09:00', '19:00']),
                array_fill(0, 2, ['09:15', '18:00']),
                array_fill(0, 2, ['09:00', '17:30']),
            );

            $this->createAttendancesByPatterns(
                $user,
                now()->copy()->startOfMonth()->subMonths($month),
                $patterns
            );
        }
    }

    private function createUser3Attendances(User $user): void
    {
        for ($month = 2; $month > 0; $month--) {

            $patterns = array_merge(
                array_fill(0, 18, ['09:00', '18:00']),
                array_fill(0, 2, ['09:00', '19:00']),
            );

            $this->createAttendancesByPatterns(
                $user,
                now()->copy()->startOfMonth()->subMonths($month),
                $patterns
            );
        }
    }

    private function createPastFiveMonths(User $user): void
    {
        for ($i = 5; $i > 0; $i--) {

            // 対象月の1日
            $date = now()->copy()->startOfMonth()->subMonths($i);

            // その月に作成した勤怠数
            $created = 0;

            while ($created < 15) {

                // 平日のみ勤怠を作成
                if ($date->isWeekday()) {

                    $this->createAttendance(
                        $user,
                        $date,
                        '09:00',
                        '18:00'
                    );

                    $created++;
                }

                // 翌日へ
                $date->addDay();
            }
        }
    }

    private function createCurrentMonth(User $user): void
    {
        $patterns = array_merge(
            array_fill(0, 10, ['09:00', '18:00']),
            array_fill(0, 3, ['09:00', '20:00']),
            array_fill(0, 2, ['09:30', '18:00']),
            [['09:00', '17:00']],
            [['08:00', '21:00']],
        );

        $this->createAttendancesByPatterns(
            $user,
            now()->copy()->startOfMonth(),
            $patterns
        );
    }

    private function createAttendance(
        User $user,
        Carbon $date,
        string $clockIn,
        string $clockOut
    ): void {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTimeFromTimeString($clockIn),
            'clock_out' => $date->copy()->setTimeFromTimeString($clockOut),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->setTime(12, 0),
            'break_end' => $date->copy()->setTime(13, 0),
        ]);
    }

    private function createAttendancesByPatterns(
        User $user,
        Carbon $date,
        array $patterns
    ): void {
        foreach ($patterns as [$clockIn, $clockOut]) {

            while (!$date->isWeekday()) {
                $date->addDay();
            }

            $this->createAttendance(
                $user,
                $date,
                $clockIn,
                $clockOut
            );

            $date->addDay();
        }
    }
}
