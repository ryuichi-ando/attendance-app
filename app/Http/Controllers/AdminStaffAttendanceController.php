<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffAttendanceController extends Controller
{
    public function index(Request $request, $userId)
    {
        // スタッフを取得
        $user = User::findOrFail($userId);

        // 表示する年月を取得
        // dateが指定されていなければ現在の月
        $date = $request->filled('date')
            ? Carbon::parse($request->date)->startOfMonth()
            : Carbon::today()->startOfMonth();

        // 前月・翌月
        $previousMonth = $date->copy()
            ->subMonth()
            ->format('Y-m');

        $nextMonth = $date->copy()
            ->addMonth()
            ->format('Y-m');

        // 指定ユーザー・指定月の勤怠を取得
        $attendanceRecords = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('attendance_date')
            ->get();

        // Bladeで使用する形式に変換
        $formattedAttendanceRecords = $attendanceRecords->map(function ($attendance) {

            // 出勤時間
            $clockIn = $attendance->start_time
                ? Carbon::parse($attendance->start_time)->format('H:i')
                : '';

            // 退勤時間
            $clockOut = $attendance->end_time
                ? Carbon::parse($attendance->end_time)->format('H:i')
                : '';

            // 休憩時間の合計（分）
            $totalBreakMinutes = 0;

            foreach ($attendance->breaks as $break) {
                if ($break->break_start && $break->break_end) {
                    $breakStart = Carbon::parse($break->break_start);
                    $breakEnd = Carbon::parse($break->break_end);

                    $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                }
            }

            // 勤務時間の合計（分）
            $totalMinutes = null;

            if ($attendance->start_time && $attendance->end_time) {
                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);

                $totalMinutes = $start->diffInMinutes($end)
                    - $totalBreakMinutes;
            }

            // 休憩時間を H:i に変換
            $totalBreakTime = $totalBreakMinutes > 0
                ? sprintf(
                    '%02d:%02d:00',
                    intdiv($totalBreakMinutes, 60),
                    $totalBreakMinutes % 60
                )
                : null;

            // 勤務時間を H:i に変換
            $totalTime = $totalMinutes !== null
                ? sprintf(
                    '%02d:%02d:00',
                    intdiv($totalMinutes, 60),
                    $totalMinutes % 60
                )
                : null;

            return [
                'id' => $attendance->id,

                'date' => Carbon::parse(
                    $attendance->attendance_date
                )->format('m/d'),

                'clock_in' => $clockIn,

                'clock_out' => $clockOut,

                'total_break_time' => $totalBreakTime,

                'total_time' => $totalTime,
            ];
        });

        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}
