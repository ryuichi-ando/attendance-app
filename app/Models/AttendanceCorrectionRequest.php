<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BreakCorrectionRequest;

class AttendanceCorrectionRequest extends Model
{
    /**
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'requested_start_time',
        'requested_end_time',
        'note',
        'status',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
    */

    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_start_time',
        'requested_end_time',
        'note',
        'status',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Blade側の $application->user に対応
     */
    public function getUserAttribute()
    {
        return $this->attendance?->user;
    }

    /**
     * Blade側の $application->approval_status に対応
     */
    public function getApprovalStatusAttribute()
    {
        return $this->status == 0
            ? '承認待ち'
            : '承認済み';
    }

    /**
     * Blade側の $application->comment に対応
     */
    public function getCommentAttribute()
    {
        return $this->note;
    }

    /**
     * Blade側の $application->application_date に対応
     */
    public function getApplicationDateAttribute()
    {
        return $this->created_at?->format('Y/m/d');
    }

    /**
     * Blade側の $application->AttendanceRecord に対応
     */
    public function getAttendanceRecordAttribute()
    {
        return $this->attendance;
    }

    /**
     * Blade側の $application->new_date に対応
     */
    public function getNewDateAttribute()
    {
        return $this->attendance?->attendance_date;
    }

    /**
     * Blade側の $application->new_clock_in に対応
     */
    public function getNewClockInAttribute()
    {
        return $this->requested_start_time
            ? \Carbon\Carbon::parse($this->requested_start_time)->format('H:i')
            : '';
    }

    public function getNewClockOutAttribute()
    {
        return $this->requested_end_time
            ? \Carbon\Carbon::parse($this->requested_end_time)->format('H:i')
            : '';
    }

    /**
     * Blade側の $application->proposalBreaks に対応
     */
    public function getProposalBreaksAttribute()
    {
        $breaks = $this->attendance?->breaks ?? collect();

        return $breaks->map(function ($break) {

            // この休憩に対する修正申請を取得
            $breakRequest = BreakCorrectionRequest::where(
                'break_id',
                $break->id
            )
                ->where('status', 0)
                ->latest()
                ->first();

            // 修正申請があれば「申請した時間」を表示
            if ($breakRequest) {
                return (object) [
                    'break_in' => $breakRequest->requested_break_start,
                    'break_out' => $breakRequest->requested_break_end,
                ];
            }

            // 修正申請がなければ元の休憩時間
            return (object) [
                'break_in' => $break->break_start,
                'break_out' => $break->break_end,
            ];
        });
    }
}
