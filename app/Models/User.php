<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function getAdminStatusAttribute()
    {
        return $this->is_admin;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getAttendanceStatusAttribute()
    {
        $attendance = $this->attendances()
            ->whereDate('attendance_date', today())
            ->latest()
            ->first();

        // 今日の勤怠記録がない場合
        if (!$attendance) {
            return '勤務外';
        }

        return match ((int) $attendance->status) {
            1 => '出勤中',
            2 => '休憩中',
            3 => '退勤済',
            default => '勤務外',
        };
    }
}
