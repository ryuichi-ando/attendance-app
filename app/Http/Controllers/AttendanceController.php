<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示
     */
    public function index()
    {
        $user = Auth::user();

        $formattedDate = now()->format('Y年m月d日');
        $formattedTime = now()->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    /**
     * 出勤・退勤・休憩の処理
     */
    public function store(AttendanceRequest $request)
    {
        $user = Auth::user();

        switch ($request->action) {

            // 出勤
            case 'clock_in':

                $attendance = $user->attendances()
                    ->whereDate('attendance_date', today())
                    ->first();

                if (!$attendance) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'attendance_date' => today(),
                        'start_time' => now()->format('H:i:s'),
                        'end_time' => null,
                        'status' => 1,
                    ]);
                }

                break;


            // 退勤
            case 'clock_out':

                $attendance = $this->getTodayAttendance($user);

                $attendance->update([
                    'end_time' => now()->format('H:i:s'),
                    'status' => 3,
                ]);

                break;


            // 休憩開始
            case 'break_in':

                $attendance = $this->getTodayAttendance($user);

                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => now()->format('H:i:s'),
                    'break_end' => null,
                ]);

                $attendance->update([
                    'status' => 2,
                ]);

                break;


            // 休憩終了
            case 'break_out':

                $attendance = $this->getTodayAttendance($user);

                $break = $attendance->breaks()
                    ->whereNull('break_end')
                    ->latest()
                    ->first();

                if ($break) {
                    $break->update([
                        'break_end' => now()->format('H:i:s'),
                    ]);

                    $attendance->update([
                        'status' => 1,
                    ]);
                }

                break;
        }

        return redirect()->route('attendance.index');
    }


    /**
     * 本日の勤怠を取得
     */
    private function getTodayAttendance($user)
    {
        return $user->attendances()
            ->whereDate('attendance_date', today())
            ->latest()
            ->firstOrFail();
    }

    /*
    public function show($id)
    {
        $user = Auth::user();

        $attendance = $user->attendances()
            ->with('breaks')
            ->findOrFail($id);

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
                    'break_in' => $break->break_start
                        ? Carbon::parse($break->break_start)->format('H:i')
                        : '',

                    'break_out' => $break->break_end
                        ? Carbon::parse($break->break_end)->format('H:i')
                        : '',
                ];
            })->toArray(),

            'comment' => '',
            'application' => null,
        ];

        return view('user.user-detail', compact(
            'user',
            'data'
        ));
    }
    */
}
