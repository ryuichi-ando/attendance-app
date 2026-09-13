<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceBreak;
use App\Models\BreakCorrectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminCorrectionRequestController extends Controller
{
    /**
     * 修正申請一覧
     */
    public function index()
    {
        $applications = AttendanceCorrectionRequest::with([
            'attendance.user',
            'attendance.breaks',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view(
            'admin.admin-application-list',
            compact('applications')
        );
    }

    /**
     * 申請詳細
     */
    public function show($id)
    {
        $application = AttendanceCorrectionRequest::with([
            'attendance.user',
            'attendance.breaks',
        ])->findOrFail($id);

        $user = $application->attendance->user;

        $attendance = $application->attendance;

        return view(
            'admin.admin-application-detail',
            compact(
                'application',
                'user',
                'attendance'
            )
        );
    }

    /**
     * 申請承認
     */
    public function approve($id)
    {
        $application = AttendanceCorrectionRequest::with([
            'attendance.breaks',
        ])->findOrFail($id);

        // すでに承認済みの場合
        if ($application->status == 1) {
            return back()->withErrors([
                'application' => 'この申請はすでに承認済みです。',
            ]);
        }

        DB::transaction(function () use ($application) {

            $attendance = $application->attendance;

            // 出勤・退勤を申請内容に更新
            $attendance->update([
                'start_time' => $application->requested_start_time,
                'end_time' => $application->requested_end_time,
            ]);

            /*
             * 休憩修正申請を取得
             */
            $breakRequests = BreakCorrectionRequest::whereIn(
                'break_id',
                $attendance->breaks->pluck('id')
            )
                ->where('status', 0)
                ->get();

            /*
             * 休憩時間を更新
             */
            foreach ($breakRequests as $breakRequest) {

                $break = AttendanceBreak::find(
                    $breakRequest->break_id
                );

                if (!$break) {
                    continue;
                }

                $break->update([
                    'break_start' =>
                        $breakRequest->requested_break_start,

                    'break_end' =>
                        $breakRequest->requested_break_end,
                ]);

                // 休憩申請を承認済みにする
                $breakRequest->update([
                    'status' => 1,
                ]);
            }

            // 勤怠修正申請を承認済みにする
            $application->update([
                'status' => 1,
            ]);
        });

        return redirect()
            ->route('admin.correction-requests.index')
            ->with('success', '修正申請を承認しました。');
    }
}
