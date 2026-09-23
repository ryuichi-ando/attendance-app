<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->startOfMonth()
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // その月の勤怠を取得
        $attendanceRecords = $user->attendances()
            ->with('breaks')
            ->whereBetween('attendance_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('attendance_date')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->attendance_date)
                    ->format('Y-m-d');
            });

        // その月の全日付を作成
        $formattedAttendanceRecords = collect();

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        for (
            $currentDate = $startOfMonth->copy();
            $currentDate->lte($endOfMonth);
            $currentDate->addDay()
        ) {
            $dateKey = $currentDate->format('Y-m-d');

            $attendance = $attendanceRecords->get($dateKey);

            // 勤怠がない日
            if (!$attendance) {
                $formattedAttendanceRecords->push([
                    'id' => null,
                    'attendance_date' => $currentDate->format('Y-m-d'),
                    'date' => $currentDate->format('m/d'),
                    'clock_in' => '',
                    'clock_out' => '',
                    'total_break_time' => null,
                    'total_time' => null,
                ]);

                continue;
            }

            // 休憩時間を計算
            $totalBreakSeconds = 0;

            foreach ($attendance->breaks as $break) {
                if ($break->break_start && $break->break_end) {
                    $breakStart = Carbon::parse($break->break_start);
                    $breakEnd = Carbon::parse($break->break_end);

                    $totalBreakSeconds +=
                        $breakStart->diffInSeconds($breakEnd);
                }
            }

            // 勤務時間を計算
            $totalTime = null;

            if ($attendance->start_time && $attendance->end_time) {
                $startTime = Carbon::parse($attendance->start_time);
                $endTime = Carbon::parse($attendance->end_time);

                $workSeconds = $startTime->diffInSeconds($endTime);
                $workSeconds -= $totalBreakSeconds;

                if ($workSeconds >= 0) {
                    $totalTime = gmdate('H:i:s', $workSeconds);
                }
            }

            // 休憩時間
            $totalBreakTime = null;

            if ($totalBreakSeconds > 0) {
                $totalBreakTime = gmdate(
                    'H:i:s',
                    $totalBreakSeconds
                );
            }

            $formattedAttendanceRecords->push([
                'id' => $attendance->id,
                'attendance_date' => $currentDate->format('Y-m-d'),
                'date' => $currentDate->format('m/d'),
                'clock_in' => $attendance->start_time
                    ? Carbon::parse($attendance->start_time)->format('H:i')
                    : '',
                'clock_out' => $attendance->end_time
                    ? Carbon::parse($attendance->end_time)->format('H:i')
                    : '',
                'total_break_time' => $totalBreakTime,
                'total_time' => $totalTime,
            ]);
        }

        return view(
            'user.user-attendance-list',
            compact(
                'formattedAttendanceRecords',
                'previousMonth',
                'nextMonth',
                'date'
            )
        );
    }
}
