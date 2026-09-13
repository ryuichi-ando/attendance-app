<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能する
     */
    public function test_clock_out_button_works_correctly(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => null,
            'status' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'end_time' => now()->format('H:i:s'),
            'status' => 3,
        ]);
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('18:00');
    }
}
