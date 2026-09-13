<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase; /** * 休憩ボタンが正しく機能する */
    public function test_break_in_button_works_correctly(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => null,
            'status' => 1,
        ]);
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_in',]);
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('breaks', ['attendance_id' => $attendance->id, 'break_end' => null,]);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 2,]);
    }

    /** 
     * 休憩は一日に何回でもできる 
     */
    public function test_break_in_can_be_done_multiple_times_per_day(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => null,
            'status' => 1,
        ]);

        // 1回目の休憩 
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_out',]);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_in',]);
        $this->assertDatabaseCount('breaks', 2);
    }

    /** * 休憩戻ボタンが正しく機能する */
    public function test_break_out_button_works_correctly(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'attendance_date' => today(), 'start_time' => '09:00:00', 'end_time' => null, 'status' => 1,]);
        $break = AttendanceBreak::create(['attendance_id' => $attendance->id, 'break_start' => '12:00:00', 'break_end' => null,]);
        $response = $this->actingAs($user)->post('/attendance', ['action' => 'break_out',]);
        $response->assertRedirect(route('attendance.index'));
        $break->refresh();
        $this->assertNotNull($break->break_end);
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 1,]);
    }

    /** * 休憩戻は一日に何回でもできる */
    public function test_break_out_can_be_done_multiple_times_per_day(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create(['user_id' => $user->id, 'attendance_date' => today(), 'start_time' => '09:00:00', 'end_time' => null, 'status' => 1,]);
        // 1回目 
        $break1 = AttendanceBreak::create(['attendance_id' => $attendance->id, 'break_start' => '12:00:00', 'break_end' => null,]);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out',]);
        // 2回目の休憩 
        $this->actingAs($user)->post('/attendance', ['action' => 'break_in',]);
        $break2 = AttendanceBreak::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($break1->refresh()->break_end);
        // 2回目の休憩戻 
        $this->actingAs($user)->post('/attendance', ['action' => 'break_out',]);
        $this->assertNotNull($break2->refresh()->break_end);
        $this->assertDatabaseCount('breaks', 2);
    }

    /** * 休憩時刻が勤怠一覧画面で確認できる */
    public function test_break_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'start_time' => '09:00:00',
            'end_time' => null,
            'status' => 1,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 12:00〜13:00 = 1時間
        $response->assertSee('1:00');
    }
}
