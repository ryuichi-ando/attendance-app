<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceReportController extends Controller
{
    /**
     * マイ勤怠レポート
     */
    public function index()
    {
        $user = Auth::user();

        // 現在日時（日本時間）
        $now = Carbon::now('Asia/Tokyo');

        /*
         * =====================================================
         * 過去6ヶ月の期間
         * =====================================================
         */

        $startMonth = $now->copy()
            ->startOfMonth()
            ->subMonths(5);

        $endMonth = $now->copy()
            ->endOfMonth();

        /*
         * =====================================================
         * 過去6ヶ月の勤怠を取得
         * =====================================================
         */

        $attendanceRecords = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [
                $startMonth->toDateString(),
                $endMonth->toDateString(),
            ])
            ->orderBy('attendance_date')
            ->get();

        /*
         * =====================================================
         * 勤怠ごとの労働時間・残業時間を計算
         * =====================================================
         */

        $calculatedRecords = $attendanceRecords->map(function ($attendance) {

            $workMinutes = null;
            $overtimeMinutes = 0;

            // 出勤・退勤の両方がある場合
            if ($attendance->start_time && $attendance->end_time) {

                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);

                // 休憩時間
                $breakMinutes = 0;

                foreach ($attendance->breaks as $break) {

                    if ($break->break_start && $break->break_end) {

                        $breakStart = Carbon::parse($break->break_start);
                        $breakEnd = Carbon::parse($break->break_end);

                        $breakMinutes += $breakStart->diffInMinutes(
                            $breakEnd
                        );
                    }
                }

                // 実労働時間
                $workMinutes = $start->diffInMinutes($end)
                    - $breakMinutes;

                /*
                 * 8時間を超えた分を残業時間とする
                 */
                if ($workMinutes > 480) {
                    $overtimeMinutes = $workMinutes - 480;
                }
            }

            return [
                'attendance' => $attendance,
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];
        });

        /*
         * =====================================================
         * FN053 基本サマリー
         * =====================================================
         */

        // 総労働時間
        $totalWorkMinutes = $calculatedRecords->sum(function ($record) {
            return $record['work_minutes'] ?? 0;
        });

        // 総残業時間
        $totalOvertimeMinutes = $calculatedRecords->sum(
            'overtime_minutes'
        );

        // 勤務日数
        $workDays = $calculatedRecords
            ->filter(function ($record) {
                return $record['work_minutes'] !== null;
            })
            ->count();

        // 平均労働時間
        $avgWorkMinutes = $workDays > 0
            ? intdiv($totalWorkMinutes, $workDays)
            : 0;

        /*
         * Bladeが期待している形式
         */
        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];

        /*
         * =====================================================
         * FN054 月次推移
         * =====================================================
         */

        $monthlyTrend = collect();

        for ($i = 0; $i < 6; $i++) {

            $month = $startMonth->copy()->addMonths($i);

            $monthlyRecords = $calculatedRecords->filter(
                function ($record) use ($month) {

                    $attendanceDate = Carbon::parse(
                        $record['attendance']->attendance_date
                    );

                    return $attendanceDate->year === $month->year
                        && $attendanceDate->month === $month->month;
                }
            );

            // 月の総労働時間
            $workMinutes = $monthlyRecords->sum(function ($record) {
                return $record['work_minutes'] ?? 0;
            });

            // 月の総残業時間
            $overtimeMinutes = $monthlyRecords->sum(
                'overtime_minutes'
            );

            $monthlyTrend->push([
                'month' => $month->format('Y/m'),
                'work_minutes' => $workMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ]);
        }

        /*
         * =====================================================
         * FN056 異常検知
         * =====================================================
         */

        // 今月の勤怠だけ取得
        $currentMonthRecords = $calculatedRecords->filter(
            function ($record) use ($now) {

                $attendanceDate = Carbon::parse(
                    $record['attendance']->attendance_date
                );

                return $attendanceDate->year === $now->year
                    && $attendanceDate->month === $now->month;
            }
        );

        /*
         * 遅刻
         * 09:00を超えて出勤した場合
         */
        $lateCount = $currentMonthRecords
            ->filter(function ($record) {

                $attendance = $record['attendance'];

                if (!$attendance->start_time) {
                    return false;
                }

                $startTime = Carbon::parse(
                    $attendance->start_time
                );

                return $startTime->format('H:i') > '09:00';
            })
            ->count();

        /*
         * 早退
         * 18:00より前に退勤した場合
         */
        $earlyLeaveCount = $currentMonthRecords
            ->filter(function ($record) {

                $attendance = $record['attendance'];

                if (!$attendance->end_time) {
                    return false;
                }

                $endTime = Carbon::parse(
                    $attendance->end_time
                );

                return $endTime->format('H:i') < '18:00';
            })
            ->count();

        /*
         * 長時間労働
         * 実労働時間が10時間を超えた場合
         */
        $longWorkCount = $currentMonthRecords
            ->filter(function ($record) {

                return $record['work_minutes'] !== null
                    && $record['work_minutes'] > 600;
            })
            ->count();

        $anomalies = [
            'late_count' => $lateCount,
            'early_leave_count' => $earlyLeaveCount,
            'long_work_count' => $longWorkCount,
        ];

        /*
         * =====================================================
         * レポート画面
         * =====================================================
         */

        return view(
            'reports.index',
            compact(
                'summary',
                'monthlyTrend',
                'anomalies'
            )
        );
    }
}
