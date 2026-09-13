<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    public function test_all_my_attendance_records_are_displayed(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-02',
            'start_time' => '09:30:00',
            'end_time' => '17:30:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('09/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('09/02');
        $response->assertSee('09:30');
        $response->assertSee('17:30');
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function test_current_month_is_displayed_when_attendance_list_is_opened(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('2026/09');
    }

    /**
     * 「前月」を押下した時に前月の情報が表示される
     */
    public function test_previous_month_is_displayed_when_previous_month_is_selected(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-08-15',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?date=2026-08');

        $response->assertStatus(200);

        $response->assertSee('2026/08');
        $response->assertSee('08/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「翌月」を押下した時に翌月の情報が表示される
     */
    public function test_next_month_is_displayed_when_next_month_is_selected(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-10-15',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?date=2026-10');

        $response->assertStatus(200);

        $response->assertSee('2026/10');
        $response->assertSee('10/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_button_redirects_to_attendance_detail(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee(
            route('attendance.detail', ['id' => $attendance->id]),
            false
        );
    }
}
