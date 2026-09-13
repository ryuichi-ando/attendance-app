<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能する
     */
    public function test_clock_in_button_works_correctly(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'attendance_date' => today()->toDateString(),
            'status' => 1,
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->first();

        $this->assertNotNull($attendance->start_time);
        $this->assertNull($attendance->end_time);
    }

    /**
     * 出勤は一日一回のみできる
     */
    public function test_clock_in_can_only_be_done_once_per_day(): void
    {
        $user = User::factory()->create();

        // 1回目の出勤
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 2回目の出勤
        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 2回目の出勤が登録されていないことを確認
        $this->assertDatabaseCount('attendances', 1);
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create();

        $clockInTime = '09:00:00';

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => $clockInTime,
            'end_time' => null,
            'status' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00');
    }
}
