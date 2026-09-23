<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
class UpdateAttendanceRecordRequest extends FormRequest
{ /** * Determine if the user is authorized to make this request. */
    public function authorize(): bool
    {
        return true;
    } /** * Get the validation rules that apply to the request. */
    public function rules(): array
    {
        return ['date' => ['sometimes', 'date'], 'clock_in' => ['sometimes', 'nullable', 'date_format:H:i'], 'clock_out' => ['sometimes', 'nullable', 'date_format:H:i'], 'comment' => ['sometimes', 'nullable', 'string'],];
    }
}
