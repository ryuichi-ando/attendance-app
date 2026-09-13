<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
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

            'new_break_in' => [
                'nullable',
                'array',
            ],

            'new_break_in.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'new_break_out' => [
                'nullable',
                'array',
            ],

            'new_break_out.*' => [
                'nullable',
                'date_format:H:i',
            ],

            'break_id' => [
                'nullable',
                'array',
            ],

            'break_id.*' => [
                'nullable',
                'integer',
            ],

            'comment' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

            'new_clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'comment.required' => '備考を記入してください',
            'comment.string' => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('new_clock_in');
            $clockOut = $this->input('new_clock_out');

            /*
             * 出勤・退勤が正しい形式の場合のみチェック
             */
            if (
                $clockIn &&
                $clockOut &&
                $this->isValidTime($clockIn) &&
                $this->isValidTime($clockOut)
            ) {
                $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);

                /*
                 * 退勤が出勤より前の場合
                 */
                if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
                    $validator->errors()->add(
                        'new_clock_out',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            /*
             * 休憩時間のチェック
             */
            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                // 両方空なら問題なし
                if (empty($breakIn) && empty($breakOut)) {
                    continue;
                }

                // 片方だけ入力されている場合
                if (empty($breakIn) || empty($breakOut)) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                if (
                    !$this->isValidTime($breakIn) ||
                    !$this->isValidTime($breakOut)
                ) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );

                    continue;
                }

                $breakInTime = Carbon::createFromFormat('H:i', $breakIn);
                $breakOutTime = Carbon::createFromFormat('H:i', $breakOut);

                /*
                 * 休憩終了が休憩開始以前の場合
                 */
                if ($breakOutTime->lessThanOrEqualTo($breakInTime)) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                /*
                 * 休憩開始が出勤より前
                 * 休憩終了が退勤より後
                 */
                if (
                    $clockIn &&
                    $this->isValidTime($clockIn) &&
                    $breakInTime->lessThan(
                        Carbon::createFromFormat('H:i', $clockIn)
                    )
                ) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                if (
                    $clockOut &&
                    $this->isValidTime($clockOut) &&
                    $breakOutTime->greaterThan(
                        Carbon::createFromFormat('H:i', $clockOut)
                    )
                ) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }

    private function isValidTime(?string $time): bool
    {
        if (!$time) {
            return false;
        }

        try {
            Carbon::createFromFormat('H:i', $time);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
