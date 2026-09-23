<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;


class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはマイ勤怠レポートにアクセスできない
     */
    public function test_guest_cannot_access_attendance_report(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    /**
     * 勤怠記録がないユーザーでもレポートを表示できる
     */
    public function test_user_without_attendance_records_can_access_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);
    }
}
