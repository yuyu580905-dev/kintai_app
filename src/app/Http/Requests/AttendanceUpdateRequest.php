<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in' => ['required'],
            'clock_out' => ['required'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.break_start' => ['nullable'],
            'breaks.*.break_end' => ['nullable'],
            'reason' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            //出勤・退勤チェック
            if ($clockIn && $clockOut && $clockIn >= $clockOut) {
                $validator->errors()->add(
                    'clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            //休憩開始チェック
            $breaks = $this->input('breaks', []);

            foreach ($breaks as $index => $break) {

                $breakStart = $break['break_start'] ?? null;

                if (!$breakStart) {
                    continue;
                }

                if ($breakStart < $clockIn || $breakStart > $clockOut) {

                    $validator->errors()->add(
                        "breaks.$index.break_start",
                        '休憩時間が不適切な値です'
                    );
                }
            }

            //休憩終了チェック
            foreach ($breaks as $index => $break) {

                $breakEnd = $break['break_end'] ?? null;

                if (!$breakEnd) {
                    continue;
                }

                if ($breakEnd > $clockOut) {

                    $validator->errors()->add(
                        "breaks.$index.break_end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}
