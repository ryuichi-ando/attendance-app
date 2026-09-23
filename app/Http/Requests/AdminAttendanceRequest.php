<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AdminAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => [
                'required',
                'date_format:H:i',
            ],

            'new_clock_out' => [
                'required',
                'date_format:H:i',
                'after:new_clock_in',
            ],

            'new_break_in.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'new_break_out.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'comment' => [
                'required',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockOut = $this->input('new_clock_out');

            if (!$clockOut) {
                return;
            }

            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {

                if (!$breakIn) {
                    continue;
                }

                $breakInTime = Carbon::createFromFormat(
                    'H:i',
                    $breakIn
                );

                $clockOutTime = Carbon::createFromFormat(
                    'H:i',
                    $clockOut
                );

                // 休憩開始 > 退勤
                if ($breakInTime->greaterThan($clockOutTime)) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩開始時間は退勤時間より前にしてください。'
                    );
                }
            }

            foreach ($breakOuts as $index => $breakOut) {

                if (!$breakOut) {
                    continue;
                }

                $breakOutTime = Carbon::createFromFormat(
                    'H:i',
                    $breakOut
                );

                $clockOutTime = Carbon::createFromFormat(
                    'H:i',
                    $clockOut
                );

                // 休憩終了 > 退勤
                if ($breakOutTime->greaterThan($clockOutTime)) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩終了時間は退勤時間より前にしてください。'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' =>
                '出勤時間は必須です。',

            'new_clock_in.date_format' =>
                '出勤時間は正しい形式で入力してください。',

            'new_clock_out.required' =>
                '退勤時間は必須です。',

            'new_clock_out.date_format' =>
                '退勤時間は正しい形式で入力してください。',

            'new_clock_out.after' =>
                '出勤時間より後の時間を入力してください。',

            'new_break_in.*.date_format' =>
                '休憩開始時間は正しい形式で入力してください。',

            'new_break_out.*.date_format' =>
                '休憩終了時間は正しい形式で入力してください。',

            'comment.required' =>
                '備考を入力してください。',
        ];
    }
}
