<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest as AttendanceCorrection;
use App\Models\AttendanceBreak;
use App\Models\BreakCorrectionRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\AttendanceCorrectionRequest;

class AttendanceDetailController extends Controller
{
    /**
     * 勤怠詳細画面を表示
     */
    public function show($id)
    {
        $attendance = Attendance::with('breaks')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $user = $attendance->user;

        // 承認待ちの修正申請を取得
        $application = AttendanceCorrection::where(
            'attendance_id',
            $attendance->id
        )
            ->where('status', 0)
            ->latest()
            ->first();

        $data = [
            'id' => $attendance->id,

            'year' => Carbon::parse($attendance->attendance_date)
                ->format('Y年'),

            'date' => Carbon::parse($attendance->attendance_date)
                ->format('m月d日'),

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

            'comment' => $application
                ? $application->note
                : '',

            'application' => $application,
        ];

        return view(
            'user.user-detail',
            compact('user', 'data')
        );
    }

    /**
     * 勤怠修正申請を登録
     */
    public function update(AttendanceCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        DB::transaction(function () use ($request, $attendance) {

            // 出勤・退勤の修正申請
            AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'requested_start_time' => $request->new_clock_in,
                'requested_end_time' => $request->new_clock_out,
                'note' => $request->comment,
                'status' => 0,
            ]);

            // 休憩の修正申請
            $breakIds = $request->input('break_id', []);
            $breakIns = $request->input('new_break_in', []);
            $breakOuts = $request->input('new_break_out', []);

            foreach ($breakIds as $index => $breakId) {

                // break_idがないものはスキップ
                if (empty($breakId)) {
                    continue;
                }

                // 実際にこの勤怠に紐づいている休憩か確認
                $break = $attendance->breaks
                    ->firstWhere('id', $breakId);

                if (!$break) {
                    continue;
                }

                // 休憩修正申請を登録
                BreakCorrectionRequest::create([
                    'break_id' => $break->id,
                    'requested_break_start' =>
                        $breakIns[$index] ?? null,
                    'requested_break_end' =>
                        $breakOuts[$index] ?? null,
                    'status' => 0,
                ]);
            }
        });

        return redirect()
            ->route('attendance.detail', [
                'id' => $attendance->id
            ])
            ->with('success', '勤怠修正申請を送信しました。');
    }
}
