<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_off_duty_status_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_working_status_is_displayed(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => null,
            'status' => 1,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_on_break_status_is_displayed(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => now()->subHour()->format('H:i:s'),
            'end_time' => null,
            'status' => 2,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->format('H:i:s'),
            'break_end' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_finished_status_is_displayed(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => now()->subHours(2)->format('H:i:s'),
            'end_time' => now()->format('H:i:s'),
            'status' => 3,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済');
    }
}