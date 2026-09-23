<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者が全一般ユーザーの氏名・メールアドレスを確認できる
     */
    public function test_admin_can_view_all_general_users_information(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'is_admin' => null,
        ]);

        $user2 = User::factory()->create([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
            'is_admin' => null,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.staff.attendance.index', [
                'userId' => $user1->id,
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('一般ユーザー1');
        $response->assertSee('user1@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_user_attendance_information_is_displayed_correctly(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'is_admin' => null,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.staff.attendance.index', [
                'userId' => $user->id,
                'date' => '2026-09',
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('2026/09');
        $response->assertSee('09/10');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「前月」を押下したとき、前月の情報が表示される
     */
    public function test_previous_month_is_displayed(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'is_admin' => null,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-08-15',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.staff.attendance.index', [
                'userId' => $user->id,
                'date' => '2026-08',
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('2026/08');
        $response->assertSee('08/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「翌月」を押下したとき、翌月の情報が表示される
     */
    public function test_next_month_is_displayed(): void
    {
        Carbon::setTestNow('2026-09-10 10:00:00');

        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'is_admin' => null,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-10-15',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.staff.attendance.index', [
                'userId' => $user->id,
                'date' => '2026-10',
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('2026/10');
        $response->assertSee('10/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面へ遷移する
     */
    public function test_detail_button_redirects_to_attendance_detail(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.staff.attendance.index', [
                'userId' => $user->id,
                'date' => '2026-09',
            ])
        );

        $response->assertStatus(200);

        $response->assertSee(
            route('admin.attendance.show', [
                'id' => $attendance->id,
            ]),
            false
        );
    }
}
