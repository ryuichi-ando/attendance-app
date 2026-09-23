<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;


class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    /**
     * GET /api/v1/attendance-records
     * 勤怠一覧がJSONで取得できる
     */
    public function test_attendance_records_index_returns_json(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => 'テスト勤怠',
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records'
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'attendance_date',
                    'working_type',
                    'start_time',
                    'end_time',
                    'break_minutes',
                    'status',
                    'note',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /**
     * GET /api/v1/attendance-records/{attendanceRecord}
     * 勤怠詳細がJSONで取得できる
     */
    public function test_attendance_record_show_returns_json(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => '詳細テスト',
        ]);

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $attendance->id
        );

        $response->assertJsonPath(
            'data.user_id',
            $user->id
        );
    }

    /**
     * 存在しないIDの場合は404のJSONを返す
     */
    public function test_attendance_record_show_returns_404_for_nonexistent_id(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/999999'
        );

        $response->assertStatus(404);

        $response->assertJson([
            'message' => '勤怠記録が見つかりません。',
        ]);
    }

    /**
     * POST /api/v1/attendance-records
     * 勤怠が作成される
     */
    public function test_attendance_record_store_creates_attendance(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/attendance-records', [
                'date' => '2026-09-20',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => 'APIテスト',
            ]);

        $response->assertStatus(201);

        $response->assertJsonPath(
            'data.user_id',
            $user->id
        );

        $response->assertJsonPath(
            'data.start_time',
            '09:00:00'
        );

        $response->assertJsonPath(
            'data.end_time',
            '18:00:00'
        );

        $response->assertJsonPath(
            'data.note',
            'APIテスト'
        );

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'attendance_date' => '2026-09-20',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => 'APIテスト',
        ]);
    }

    /**
     * POST /api/v1/attendance-records
     * バリデーションエラーの場合は422
     */
    public function test_attendance_record_store_returns_422_for_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/attendance-records', [
                'date' => '2026/09/20',
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ]);

        $response->assertStatus(422);

        $response->assertJson([
            'message' => '入力内容に誤りがあります。',
        ]);

        $response->assertJsonValidationErrors([
            'date',
            'clock_in',
            'clock_out',
        ]);
    }

    /**
     * PUT /api/v1/attendance-records/{attendanceRecord}
     * 勤怠が更新される
     */
    public function test_attendance_record_update_updates_attendance(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => '更新前',
        ]);

        $this->assertTrue(
            $user->can('update', $attendance)
        );

        $response = $this->actingAs($user, 'sanctum')
            ->putJson(
                "/api/v1/attendance-records/{$attendance->id}",
                [
                    'clock_in' => '10:00:00',
                    'clock_out' => '19:00:00',
                    'comment' => '更新後',
                ]
            );

        $response->assertStatus(200);

        $updatedAttendance = Attendance::find($attendance->id);

        $this->assertSame(
            '10:00:00',
            $updatedAttendance->start_time
        );

        $response->assertJsonPath(
            'data.start_time',
            '10:00:00'
        );

        $response->assertJsonPath(
            'data.end_time',
            '19:00:00'
        );

        $response->assertJsonPath(
            'data.note',
            '更新後'
        );

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00',
            'note' => '更新後',
        ]);
    }

    /**
     * PUT /api/v1/attendance-records/{attendanceRecord}
     * 未認証の場合は401
     */
    public function test_unauthenticated_user_cannot_update_attendance_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => '未認証更新テスト',
        ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '未認証更新',
            ]
        );

        $response->assertStatus(401);
    }

    /**
     * PUT /api/v1/attendance-records/{attendanceRecord}
     * 他ユーザーの勤怠は更新できず403
     */
    public function test_authenticated_user_cannot_update_another_users_attendance_record(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => 'ユーザー2の勤怠',
        ]);

        // ユーザー1としてログイン
        Sanctum::actingAs($user1);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendance->id}",
            [
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => 'ユーザー1から更新しようとする',
            ]
        );

        $response->assertStatus(403);

        // 勤怠が変更されていないことも確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user2->id,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'note' => 'ユーザー2の勤怠',
        ]);
    }

    /**
     * DELETE /api/v1/attendance-records/{attendanceRecord}
     * 勤怠が削除される
     */
    public function test_attendance_record_destroy_deletes_attendance(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => '削除テスト',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson(
                "/api/v1/attendance-records/{$attendance->id}"
            );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /**
     * DELETE /api/v1/attendance-records/{attendanceRecord}
     * 未認証の場合は401
     */
    public function test_unauthenticated_user_cannot_delete_attendance_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => '未認証削除テスト',
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(401);
    }

    /**
     * DELETE /api/v1/attendance-records/{attendanceRecord}
     * 他ユーザーの勤怠は削除できず403
     */
    public function test_authenticated_user_cannot_delete_another_users_attendance_record(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user2->id,
            'attendance_date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 3,
            'note' => 'ユーザー2の削除対象',
        ]);

        // ユーザー1としてログイン
        Sanctum::actingAs($user1);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendance->id}"
        );

        $response->assertStatus(403);

        // 削除されていないことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user2->id,
        ]);
    }
}
