<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AttendanceBreak;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'reason',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function latestBreak()
    {
        return $this->breaks()
            ->latest('id')
            ->first();
    }

    public function status()
    {
        // 1. 退勤時刻（clock_out）がすでに入っている場合は「退勤済」
        if ($this->clock_out !== null) {
            return '退勤済';
        }

        // 最後の休憩レコードを取得
        $latestBreak = $this->latestBreak();

        // 2. 休憩レコードが存在し、かつ「休憩終了時刻（break_end）」がまだ空（null）なら「休憩中」
        if ($latestBreak !== null && $latestBreak->break_end === null) {
            return '休憩中';
        }

        // 3. 上記以外（退勤しておらず、休憩中でもない）なら「出勤中」
        return '出勤中';
    }

    public function startBreak()
    {
        $this->breaks()->create([
            'break_start' => now(),
        ]);
    }

    public function endBreak()
    {
        $latestBreak = $this->latestBreak();

        if ($latestBreak !== null) {
            $latestBreak->update([
                'break_end' => now(),
            ]);
        }
    }

    public function clockOut()
    {
        $this->update([
            'clock_out' => now(),
        ]);
    }

    public function breakMinutes()
    {
        return $this->breaks->sum(function ($break) {

            if ($break->break_start === null || $break->break_end === null) {
                return 0;
            }

            return $break->break_start->diffInMinutes($break->break_end);
        });
    }

    public function workingMinutes()
    {
        if ($this->clock_in === null || $this->clock_out === null) {
            return null;
        }

        return $this->clock_in->diffInMinutes($this->clock_out)
            - $this->breakMinutes();
    }

    public function formattedBreakTime()
    {
        $minutes = $this->breakMinutes();

        return sprintf(
            '%02d:%02d',
            floor($minutes / 60),
            $minutes % 60
        );
    }

    public function formattedWorkingTime()
    {
        $minutes = $this->workingMinutes();

        if ($minutes === null) {
            return null;
        }

        return sprintf(
            '%02d:%02d',
            floor($minutes / 60),
            $minutes % 60
        );
    }
}
