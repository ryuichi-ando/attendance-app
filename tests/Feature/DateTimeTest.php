<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    /** @test */
    public function test_current_datetime_is_displayed_in_ui_format(): void
    {
        // 現在日時を固定
        Carbon::setTestNow(
            Carbon::create(2026, 9, 10, 19, 30, 0)
        );

        // テスト用ユーザーを作成
        $user = User::factory()->create();

        // ログイン状態にする
        $this->actingAs($user);

        // 勤怠登録画面を表示
        $response = $this->get('/attendance');

        // 正常に画面が表示されることを確認
        $response->assertStatus(200);

        // 日付がUI用の形式になっていることを確認
        $response->assertViewHas(
            'formattedDate',
            '2026年09月10日'
        );

        // 時刻がUI用の形式になっていることを確認
        $response->assertViewHas(
            'formattedTime',
            '19:30'
        );

        // 現在日時の固定を解除
        Carbon::setTestNow();
    }
}