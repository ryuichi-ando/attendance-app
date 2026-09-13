<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * レポート画面を表示
     */
    public function index()
    {
        $user = Auth::user();

        // 現在の年月
        $year = now()->year;
        $month = now()->month;

        // 指定月の勤怠を取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->get();

        // 出勤日数
        $attendanceDays = $attendances->filter(function ($attendance) {
            return $attendance->start_time !== null;
        })->count();

        // 総勤務時間（分）
        $totalWorkMinutes = 0;

        // 総休憩時間（分）
        $totalBreakMinutes = 0;

        foreach ($attendances as $attendance) {

            // 出勤・退勤時間
            if ($attendance->start_time && $attendance->end_time) {
                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);

                $totalWorkMinutes += $start->diffInMinutes($end);
            }

            // 休憩時間
            foreach ($attendance->breaks as $break) {
                if ($break->break_start && $break->break_end) {
                    $breakStart = Carbon::parse($break->break_start);
                    $breakEnd = Carbon::parse($break->break_end);

                    $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
                }
            }
        }

        // 時間・分に変換
        $totalWorkHours = intdiv($totalWorkMinutes, 60);
        $totalWorkMinutesRest = $totalWorkMinutes % 60;

        $totalBreakHours = intdiv($totalBreakMinutes, 60);
        $totalBreakMinutesRest = $totalBreakMinutes % 60;

        return view('user.user-report', compact(
            'user',
            'year',
            'month',
            'attendances',
            'attendanceDays',
            'totalWorkHours',
            'totalWorkMinutesRest',
            'totalBreakHours',
            'totalBreakMinutesRest'
        ));
    }
}
