<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    /**
     * 申請一覧画面を表示
     */
    public function index()
    {
        // ログイン中のユーザーを取得
        $user = Auth::user();

        // ログイン中のユーザーが行った勤怠修正申請を取得
        $applications = AttendanceCorrectionRequest::with('attendance')
            ->whereHas('attendance', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedApplications = $applications->map(function ($application) {
            return [
                'id' => $application->id,

                // 0 = 承認待ち、1 = 承認済み
                'approval_status' => $application->status === 0
                    ? '承認待ち'
                    : '承認済み',

                // 対象となる勤怠日
                'date' => Carbon::parse(
                    $application->attendance->attendance_date
                )->format('Y/m/d'),

                // 申請理由
                'comment' => $application->note,

                // 申請日時
                'application_date' => Carbon::parse(
                    $application->created_at
                )->format('Y/m/d'),
            ];
        });

        return view(
            'user.user-application-list',
            compact('user', 'formattedApplications')
        );
    }

    public function show($id)
    {
        $application = AttendanceCorrectionRequest::with('attendance')
            ->whereHas('attendance', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        return redirect()->route(
            'attendance.detail',
            ['id' => $application->attendance_id]
        );
    }
}
