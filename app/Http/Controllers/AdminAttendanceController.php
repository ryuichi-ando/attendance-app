<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\AdminAttendanceRequest;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者用勤怠一覧
     */
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 一般会員
        $users = User::whereNull('is_admin')->get();

        // 指定日の勤怠
        $attendanceRecords = Attendance::with('breaks')
            ->whereDate(
                'attendance_date',
                $date->format('Y-m-d')
            )
            ->get();

        // 休憩時間・合計勤務時間を計算
        $attendanceRecords->each(function ($attendance) {

            $attendance->clock_in = $attendance->start_time;
            $attendance->clock_out = $attendance->end_time;

            // -------------------------
            // 休憩時間の合計
            // -------------------------
            $totalBreakSeconds = 0;

            foreach ($attendance->breaks as $break) {

                if ($break->break_start && $break->break_end) {

                    $breakStart = Carbon::parse(
                        $break->break_start
                    );

                    $breakEnd = Carbon::parse(
                        $break->break_end
                    );

                    $totalBreakSeconds +=
                        $breakStart->diffInSeconds($breakEnd);
                }
            }

            // Bladeで使う値を追加
            $attendance->total_break_time = $totalBreakSeconds > 0
                ? gmdate('H:i:s', $totalBreakSeconds)
                : '00:00:00';


            // -------------------------
            // 合計勤務時間
            // -------------------------
            $attendance->total_time = null;

            if (
                $attendance->start_time &&
                $attendance->end_time
            ) {

                $startTime = Carbon::parse(
                    $attendance->start_time
                );

                $endTime = Carbon::parse(
                    $attendance->end_time
                );

                $workSeconds =
                    $startTime->diffInSeconds($endTime);

                // 休憩時間を引く
                $workSeconds -= $totalBreakSeconds;

                if ($workSeconds >= 0) {
                    $attendance->total_time =
                        gmdate('H:i:s', $workSeconds);
                }
            }
        });

        return view(
            'admin.admin-attendance-list',
            compact(
                'date',
                'previousDay',
                'nextDay',
                'users',
                'attendanceRecords'
            )
        );
    }

    /**
     * 一般会員の勤怠詳細
     */
    public function show($id)
    {
        $attendance = Attendance::with([
            'user',
            'breaks'
        ])->findOrFail($id);

        $user = $attendance->user;

        $attendanceRecord = [
            'id' => $attendance->id,

            'year' => Carbon::parse(
                $attendance->attendance_date
            )->format('Y年'),

            'date' => Carbon::parse(
                $attendance->attendance_date
            )->format('m月d日'),

            'clock_in' => $attendance->start_time
                ? Carbon::parse($attendance->start_time)->format('H:i')
                : '',

            'clock_out' => $attendance->end_time
                ? Carbon::parse($attendance->end_time)->format('H:i')
                : '',

            'breaks' => $attendance->breaks->map(function ($break) {
                return [
                    'id' => $break->id,

                    'break_in' => $break->break_start
                        ? Carbon::parse($break->break_start)->format('H:i')
                        : '',

                    'break_out' => $break->break_end
                        ? Carbon::parse($break->break_end)->format('H:i')
                        : '',
                ];
            })->toArray(),

            'comment' => '',
        ];

        return view(
            'admin.admin-detail',
            compact(
                'user',
                'attendanceRecord'
            )
        );
    }

    /**
     * 管理者が勤怠を直接修正
     */
    public function update(AdminAttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')
            ->findOrFail($id);

        // 出勤・退勤を直接更新
        $attendance->update([
            'start_time' => $request->new_clock_in,
            'end_time' => $request->new_clock_out,
        ]);

        // 休憩時間を更新
        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);

        foreach ($attendance->breaks as $index => $break) {
            if (
                isset($breakIns[$index]) &&
                isset($breakOuts[$index])
            ) {
                $break->update([
                    'break_start' => $breakIns[$index],
                    'break_end' => $breakOuts[$index],
                ]);
            }
        }

        // 新しい休憩が入力されている場合
        $newIndex = $attendance->breaks->count();

        if (
            !empty($breakIns[$newIndex]) &&
            !empty($breakOuts[$newIndex])
        ) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_start' => $breakIns[$newIndex],
                'break_end' => $breakOuts[$newIndex],
            ]);
        }

        return redirect()
            ->route('admin.attendance.show', [
                'id' => $attendance->id
            ])
            ->with('success', '勤怠を修正しました。');
    }
}
