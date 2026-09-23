<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_selected_attendance_detail_is_displayed(): void
    {
        // 管理者
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        // 一般ユーザー
        $user = User::factory()->create([
            'is_admin' => null,
            'name' => 'テストユーザー',
        ]);

        // 勤怠
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => 'テスト備考',
        ]);

        // 休憩
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            route('admin.attendance.show', [
                'id' => $attendance->id,
            ])
        );

        $response->assertStatus(200);

        // 選択したユーザー
        $response->assertSee('テストユーザー');

        // 選択した勤怠の日付
        $response->assertSee('2026年');
        $response->assertSee('09月19日');

        // 出退勤時間
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 休憩時間
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }


    /**
     * 出勤時間が退勤時間より後になっている場合、
     * エラーメッセージが表示される
     */
    public function test_error_is_displayed_when_clock_in_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            route('admin.attendance.update', [
                'id' => $attendance->id,
            ]),
            [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'comment' => '時間修正テスト',
            ]
        );

        $response->assertSessionHasErrors('new_clock_out');

        $this->assertEquals(
            '18:00:00',
            $attendance->fresh()->end_time
        );
    }


    /**
     * 休憩開始時間が退勤時間より後になっている場合、
     * エラーメッセージが表示される
     */
    public function test_error_is_displayed_when_break_start_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            route('admin.attendance.update', [
                'id' => $attendance->id,
            ]),
            [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [
                    0 => '19:00',
                ],
                'new_break_out' => [
                    0 => '19:30',
                ],
                'comment' => '休憩開始時間テスト',
            ]
        );

        $response->assertSessionHasErrors('new_break_in.0');
    }


    /**
     * 休憩終了時間が退勤時間より後になっている場合、
     * エラーメッセージが表示される
     */
    public function test_error_is_displayed_when_break_end_is_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '17:00:00',
            'break_end' => '17:30:00',
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            route('admin.attendance.update', [
                'id' => $attendance->id,
            ]),
            [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [
                    0 => '17:00',
                ],
                'new_break_out' => [
                    0 => '19:00',
                ],
                'comment' => '休憩終了時間テスト',
            ]
        );

        $response->assertSessionHasErrors('new_break_out.0');
    }


    /**
     * 備考欄が未入力の場合、エラーメッセージが表示される
     */
    public function test_error_is_displayed_when_comment_is_empty(): void
    {
        $admin = User::factory()->create([
            'is_admin' => 1,
        ]);

        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-19',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            route('admin.attendance.update', [
                'id' => $attendance->id,
            ]),
            [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '',
            ]
        );

        $response->assertSessionHasErrors('comment');
    }
}
