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
        $user = Auth::user();

        /*
         * 勤怠IDとして存在するか確認
         */
        $attendance = Attendance::with('breaks')
            ->where('user_id', Auth::id())
            ->find($id);

        /*
         * 勤怠IDが存在しない場合は、
         * $idを日付として扱う
         */
        if (!$attendance) {
            try {
                $attendanceDate = Carbon::parse($id)->format('Y-m-d');
            } catch (\Exception $e) {
                abort(404);
            }

            /*
             * そのユーザーのその日の勤怠を検索
             */
            $attendance = Attendance::with('breaks')
                ->where('user_id', Auth::id())
                ->whereDate('attendance_date', $attendanceDate)
                ->first();
        }

        /*
         * 勤怠が存在しない日
         */
        if (!$attendance) {

            $attendanceDate = Carbon::parse($id);

            $data = [
                'id' => null,

                'year' => $attendanceDate->format('Y年'),

                'date' => $attendanceDate->format('m月d日'),

                'clock_in' => '',

                'clock_out' => '',

                'breaks' => [],

                'comment' => '',

                'application' => null,
            ];

            return view(
                'user.user-detail',
                compact('user', 'data')
            );
        }

        /*
         * 勤怠が存在する場合
         */

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
            $breakIns = $request->input('new_break_in', []);
            $breakOuts = $request->input('new_break_out', []);

            // 既存の休憩をインデックス順に取得
            $breaks = $attendance->breaks->values();

            foreach ($breakIns as $index => $breakIn) {

                $breakOut = $breakOuts[$index] ?? null;

                // 両方とも空なら申請しない
                if (empty($breakIn) && empty($breakOut)) {
                    continue;
                }

                // 既存の休憩が存在する場合
                $break = $breaks->get($index);

                if (!$break) {
                    // 新しい休憩については、
                    // break_idが存在しないため現時点では保存しない
                    continue;
                }

                BreakCorrectionRequest::create([
                    'break_id' => $break->id,
                    'requested_break_start' => $breakIn,
                    'requested_break_end' => $breakOut,
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
