<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧取得
     */
    public function index(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $attendanceRecords = Attendance::query()

            // user_id検索
            ->when(
                $request->filled('user_id'),
                function ($query) use ($request) {
                    $query->where(
                        'user_id',
                        $request->input('user_id')
                    );
                }
            )

            // 日付検索
            ->when(
                $request->filled('date'),
                function ($query) use ($request) {
                    $query->whereDate(
                        'attendance_date',
                        $request->input('date')
                    );
                }
            )

            // 月検索
            ->when(
                $request->filled('month'),
                function ($query) use ($request) {

                    $month = $request->input('month');

                    $query->whereBetween(
                        'attendance_date',
                        [
                            $month . '-01',
                            date(
                                'Y-m-t',
                                strtotime($month . '-01')
                            ),
                        ]
                    );
                }
            )

            ->orderBy('attendance_date', 'desc')
            ->paginate($perPage);

        return AttendanceRecordResource::collection(
            $attendanceRecords
        );
    }

    /**
     * 勤怠詳細取得
     */
    public function show(Attendance $attendanceRecord)
    {
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠作成
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $attendance = Attendance::create([
            'user_id' => $request->user()->id,

            'attendance_date' => $request->input('date'),

            'start_time' => $request->input('clock_in'),

            'end_time' => $request->input('clock_out'),

            'note' => $request->input('comment'),

            // 新規登録時の初期状態
            'status' => $request->filled('clock_out')
                ? 3
                : 1,
        ]);

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠更新
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        Attendance $attendanceRecord
    ) {
        $this->authorize('update', $attendanceRecord);

        $data = $request->validated();

        $updateData = [];

        if (array_key_exists('date', $data)) {
            $updateData['attendance_date'] = $data['date'];
        }

        if (array_key_exists('clock_in', $data)) {
            $updateData['start_time'] = $data['clock_in'];
        }

        if (array_key_exists('clock_out', $data)) {
            $updateData['end_time'] = $data['clock_out'];

            $updateData['status'] =
                $data['clock_out'] !== null ? 3 : 1;
        }

        if (array_key_exists('comment', $data)) {
            $updateData['note'] = $data['comment'];
        }

        $attendanceRecord->update($updateData);

        $attendanceRecord->refresh();

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * 勤怠削除
     */
    public function destroy(Attendance $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);
        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
