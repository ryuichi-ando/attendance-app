<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Attendance;

class StoreAttendanceRecordRequest extends FormRequest
{
    /**
     * APIなので認証済みユーザーによる登録を許可
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'clock_in' => [
                'required',
                'date_format:H:i:s',
            ],

            'clock_out' => [
                'nullable',
                'date_format:H:i:s',
                'after:clock_in',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * 日本語エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'date.required' =>
                '勤怠日は必須です。',

            'date.date_format' =>
                '勤怠日はYYYY-MM-DD形式で指定してください。',

            'clock_in.required' =>
                '出勤時刻は必須です。',

            'clock_in.date_format' =>
                '出勤時刻はHH:MM:SS形式で指定してください。',

            'clock_out.date_format' =>
                '退勤時刻はHH:MM:SS形式で指定してください。',

            'clock_out.after' =>
                '退勤時刻は出勤時刻より後の時刻を指定してください。',

            'comment.max' =>
                '備考は255文字以内で入力してください。',
        ];
    }

    /**
     * user_id + date の重複チェック
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $user = $this->user();

            if (!$user || !$this->filled('date')) {
                return;
            }

            $exists = Attendance::where('user_id', $user->id)
                ->whereDate(
                    'attendance_date',
                    $this->input('date')
                )
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'date',
                    'この日付の勤怠は既に登録されています。'
                );
            }
        });
    }
}
