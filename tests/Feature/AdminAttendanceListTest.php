<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日の全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_all_users_attendance_for_the_day_is_displayed(): void
    {
        Carbon::setTestNow('2026-09-19 10:00:00');

        // 管理者
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        // 一般ユーザー
        $user1 = User::factory()->create([
            'is_admin' => null,
            'name' => 'ユーザー1',
        ]);

        $user2 = User::factory()->create([
            'is_admin' => null,
            'name' => 'ユーザー2',
        ]);

        // 2026/09/19の勤怠
        Attendance::create([
            'user_id' => $user1->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:30:00',
            'end_time' => '17:30:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.attendance.index')
        );

        $response->assertStatus(200);

        // ユーザー1の勤怠
        $response->assertSee('ユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // ユーザー2の勤怠
        $response->assertSee('ユーザー2');
        $response->assertSee('09:30');
        $response->assertSee('17:30');

        Carbon::setTestNow();
    }


    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_is_displayed_when_admin_attendance_list_is_opened(): void
    {
        Carbon::setTestNow('2026-09-19 10:00:00');

        // 管理者
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.attendance.index')
        );

        $response->assertStatus(200);

        // Controllerに現在の日付が渡されていることを確認
        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-09-19';
        });

        Carbon::setTestNow();
    }


    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_previous_day_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-09-19 10:00:00');

        // 管理者
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        // 前日の一般ユーザー
        $user = User::factory()->create([
            'is_admin' => null,
            'name' => '前日ユーザー',
        ]);

        // 2026/09/18の勤怠
        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        // 前日を指定
        $response = $this->get(
            route('admin.attendance.index', [
                'date' => '2026-09-18',
            ])
        );

        $response->assertStatus(200);

        // 前日の情報が表示される
        $response->assertSee('前日ユーザー');
        $response->assertSee('08:30');
        $response->assertSee('17:30');

        // 表示対象の日付も確認
        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-09-18';
        });

        Carbon::setTestNow();
    }


    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_next_day_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-09-19 10:00:00');

        // 管理者
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        // 翌日の一般ユーザー
        $user = User::factory()->create([
            'is_admin' => null,
            'name' => '翌日ユーザー',
        ]);

        // 2026/09/20の勤怠
        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-20',
            'start_time' => '09:30:00',
            'end_time' => '18:30:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        // 翌日を指定
        $response = $this->get(
            route('admin.attendance.index', [
                'date' => '2026-09-20',
            ])
        );

        $response->assertStatus(200);

        // 翌日の情報が表示される
        $response->assertSee('翌日ユーザー');
        $response->assertSee('09:30');
        $response->assertSee('18:30');

        // 表示対象の日付も確認
        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-09-20';
        });

        Carbon::setTestNow();
    }
}
