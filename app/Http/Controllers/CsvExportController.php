<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportController extends Controller
{
    /**
     * ユーザー別・月次勤怠CSV出力
     */
    public function export(Request $request)
    {
        // -------------------------
        // 入力値を取得
        // -------------------------
        $userId = $request->input('user_id');
        $yearMonth = $request->input('year_month');

        // -------------------------
        // ユーザーを取得
        // -------------------------
        $user = User::findOrFail($userId);

        // -------------------------
        // 年月を取得
        // -------------------------
        $date = Carbon::createFromFormat(
            'Y-m',
            $yearMonth
        )->startOfMonth();

        // -------------------------
        // 指定ユーザー・指定月の勤怠を取得
        // -------------------------
        $attendanceRecords = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('attendance_date')
            ->get();

        // -------------------------
        // ファイル名
        // -------------------------
        $fileName = $user->name . '_' . $date->format('Y-m') . '_勤怠一覧.csv';

        // -------------------------
        // CSVを出力
        // -------------------------
        return new StreamedResponse(function () use ($attendanceRecords) {

            $handle = fopen('php://output', 'w');

            // Excelで文字化けしにくくするためBOMを追加
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            // -------------------------
            // 勤怠データ
            // -------------------------
            foreach ($attendanceRecords as $attendance) {

                // 出勤
                $clockIn = $attendance->start_time
                    ? Carbon::parse(
                        $attendance->start_time
                    )->format('H:i')
                    : '';

                // 退勤
                $clockOut = $attendance->end_time
                    ? Carbon::parse(
                        $attendance->end_time
                    )->format('H:i')
                    : '';

                // 休憩時間
                $totalBreakMinutes = 0;

                foreach ($attendance->breaks as $break) {

                    if (
                        $break->break_start &&
                        $break->break_end
                    ) {
                        $breakStart = Carbon::parse(
                            $break->break_start
                        );

                        $breakEnd = Carbon::parse(
                            $break->break_end
                        );

                        $totalBreakMinutes +=
                            $breakStart->diffInMinutes(
                                $breakEnd
                            );
                    }
                }

                $totalBreakTime = $totalBreakMinutes > 0
                    ? sprintf(
                        '%02d:%02d',
                        intdiv($totalBreakMinutes, 60),
                        $totalBreakMinutes % 60
                    )
                    : '';

                // 合計勤務時間
                $totalTime = '';

                if (
                    $attendance->start_time &&
                    $attendance->end_time
                ) {
                    $start = Carbon::parse(
                        $attendance->start_time
                    );

                    $end = Carbon::parse(
                        $attendance->end_time
                    );

                    $totalMinutes =
                        $start->diffInMinutes($end)
                        - $totalBreakMinutes;

                    if ($totalMinutes >= 0) {
                        $totalTime = sprintf(
                            '%02d:%02d',
                            intdiv($totalMinutes, 60),
                            $totalMinutes % 60
                        );
                    }
                }

                // CSVに1行追加
                fputcsv($handle, [
                    Carbon::parse(
                        $attendance->attendance_date
                    )->format('Y/m/d'),
                    $clockIn,
                    $clockOut,
                    $totalBreakTime,
                    $totalTime,
                ]);
            }

            fclose($handle);

        }, 200, [
            'Content-Type' =>
                'text/csv; charset=UTF-8',

            'Content-Disposition' =>
                'attachment; filename="' . $fileName . '"',
        ]);
    }
}
