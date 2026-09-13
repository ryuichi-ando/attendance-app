<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_displays_logged_in_user_name(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_displays_selected_date(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee('2026年');
        $response->assertSee('09月10日');
    }

    /**
     * 「出勤・退勤」に記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_clock_in_and_out_time(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「休憩」に記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_displays_correct_break_time(): void
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $response->assertStatus(200);

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }



    /**
     * 勤怠詳細画面が表示される
     */
    public function test_attendance_detail_page_can_be_displayed(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
    }

    /**
     * 出勤時間が退勤時間より後の場合、エラーになる
     */
    public function test_clock_in_after_clock_out_returns_validation_error(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'comment' => '出勤時間を修正します',
                'break_id' => [],
                'new_break_in' => [],
                'new_break_out' => [],
            ]);

        $response->assertSessionHasErrors('new_clock_in');
    }

    /**
     * 休憩開始時間が退勤時間より後の場合、エラーになる
     */
    public function test_break_start_after_clock_out_returns_validation_error(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'break_id' => [$break->id],
                'new_break_in' => ['19:00'],
                'new_break_out' => ['20:00'],
                'comment' => '休憩時間を修正します',
            ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩終了時間が退勤時間より後の場合、エラーになる
     */
    public function test_break_end_after_clock_out_returns_validation_error(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'break_id' => [$break->id],
                'new_break_in' => ['17:00'],
                'new_break_out' => ['19:00'],
                'comment' => '休憩時間を修正します',
            ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 備考欄が未入力の場合、エラーになる
     */
    public function test_comment_required(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '',
                'break_id' => [],
                'new_break_in' => [],
                'new_break_out' => [],
            ]);

        $response->assertSessionHasErrors('comment');
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_attendance_correction_request_is_created(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '10:00',
                'new_clock_out' => '19:00',
                'comment' => '出退勤時間を修正します',
                'break_id' => [],
                'new_break_in' => [],
                'new_break_out' => [],
            ]);

        $response->assertRedirect(
            route('attendance.detail', ['id' => $attendance->id])
        );

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_start_time' => '10:00',
            'requested_end_time' => '19:00',
            'note' => '出退勤時間を修正します',
            'status' => 0,
        ]);
    }

    /**
     * 休憩時間の修正申請も登録される
     */
    public function test_break_correction_request_is_created(): void
    {
        $user = User::factory()->create([
            'is_admin' => null,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-10',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 0,
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('attendance.detail.update', ['id' => $attendance->id]), [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '休憩時間を修正します',
                'break_id' => [$break->id],
                'new_break_in' => ['12:30'],
                'new_break_out' => ['13:30'],
            ]);

        $this->assertDatabaseHas('break_correction_requests', [
            'break_id' => $break->id,
            'requested_break_start' => '12:30',
            'requested_break_end' => '13:30',
            'status' => 0,
        ]);
    }
}
