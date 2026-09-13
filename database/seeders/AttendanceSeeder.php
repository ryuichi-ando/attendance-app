<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();
        $user3 = User::where('email', 'user3@example.com')->first();

        /*
         * ==========================================
         * user1
         * ==========================================
         */

        // 過去5ヶ月：各月平日15日
        $this->createUser1PastRecords($user1);

        // 当月17日：指定されたパターン
        $this->createUser1CurrentRecords($user1);


        /*
         * ==========================================
         * user2
         * user3
         * ==========================================
         */

        // 実運用に近いダミーデータ
        $this->createNormalDummyRecords($user2);
        $this->createNormalDummyRecords($user3);
    }


    /**
     * user1
     *
     * 過去5ヶ月
     * 各月平日15日
     * 9:00 - 18:00
     */
    private function createUser1PastRecords(User $user): void
    {
        $currentMonth = Carbon::now()->startOfMonth();

        // 現在の月から5ヶ月前まで
        for ($i = 5; $i >= 1; $i--) {

            $month = $currentMonth->copy()->subMonths($i);

            $createdCount = 0;
            $date = $month->copy()->startOfMonth();

            while ($createdCount < 15) {

                // 土日を除外
                if ($date->isWeekday()) {

                    $this->createAttendance(
                        user: $user,
                        date: $date,
                        startTime: '09:00:00',
                        endTime: '18:00:00',
                        status: 3
                    );

                    $createdCount++;
                }

                $date->addDay();
            }
        }
    }


    /**
     * user1
     *
     * 当月17日
     *
     * 通常       10日
     * 残業        3日
     * 遅刻        2日
     * 早退        1日
     * 長時間労働  1日
     */
    private function createUser1CurrentRecords(User $user): void
    {
        $currentMonth = Carbon::now()->startOfMonth();

        $dates = [];
        $date = $currentMonth->copy();

        while (count($dates) < 17) {

            if ($date->isWeekday()) {
                $dates[] = $date->copy();
            }

            $date->addDay();
        }


        /*
         * 通常勤務 10日
         */
        for ($i = 0; $i < 10; $i++) {

            $this->createAttendance(
                user: $user,
                date: $dates[$i],
                startTime: '09:00:00',
                endTime: '18:00:00',
                status: 3
            );
        }


        /*
         * 残業 3日
         * 09:00 - 20:00
         *
         * 休憩1時間なので
         * 実労働時間は10時間
         * → 2時間の残業
         */
        for ($i = 10; $i < 13; $i++) {

            $this->createAttendance(
                user: $user,
                date: $dates[$i],
                startTime: '09:00:00',
                endTime: '20:00:00',
                status: 3
            );
        }


        /*
         * 遅刻 2日
         * 09:30 - 18:00
         */
        for ($i = 13; $i < 15; $i++) {

            $this->createAttendance(
                user: $user,
                date: $dates[$i],
                startTime: '09:30:00',
                endTime: '18:00:00',
                status: 3
            );
        }


        /*
         * 早退 1日
         * 09:00 - 17:00
         */
        $this->createAttendance(
            user: $user,
            date: $dates[15],
            startTime: '09:00:00',
            endTime: '17:00:00',
            status: 3
        );


        /*
         * 長時間労働 1日
         * 08:00 - 21:00
         *
         * 休憩1時間
         * 実労働時間12時間
         * → 4時間の残業
         */
        $this->createAttendance(
            user: $user,
            date: $dates[16],
            startTime: '08:00:00',
            endTime: '21:00:00',
            status: 3
        );
    }


    /**
     * user2 / user3用
     *
     * 過去6ヶ月分の平日データを作成
     */
    private function createNormalDummyRecords(User $user): void
    {
        $currentMonth = Carbon::now()->startOfMonth();

        for ($i = 5; $i >= 0; $i--) {

            $month = $currentMonth->copy()->subMonths($i);

            $createdCount = 0;
            $date = $month->copy()->startOfMonth();

            while ($date->month === $month->month) {

                if ($date->isWeekday()) {

                    $this->createAttendance(
                        user: $user,
                        date: $date,
                        startTime: '09:00:00',
                        endTime: '18:00:00',
                        status: 3
                    );

                    $createdCount++;
                }

                $date->addDay();
            }
        }
    }


    /**
     * 勤怠レコード作成
     */
    private function createAttendance(
        User $user,
        Carbon $date,
        string $startTime,
        string $endTime,
        int $status
    ): void {

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'note' => null,
        ]);


        /*
         * 固定休憩
         * 12:00 - 13:00
         */
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }
}
