<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],

            'start_datetime' => [
                'required',
                'date',
                'after_or_equal:now',
            ],

            'end_datetime' => [
                'required',
                'date',
                'after:start_datetime',
            ],

            'recurrence_type' => [
                'nullable',
                'in:none,daily,weekly,monthly',
            ],

            'recurrence_days' => [
                'nullable',
                'string',
            ],

            'recurrence_period' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'recurrence_until' => [
                'nullable',
                'date',
                'after:start_datetime',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Room is required.',
            'room_id.exists' => 'Selected room does not exist.',

            'start_datetime.required' => 'Start datetime is required.',
            'start_datetime.date' => 'Start datetime must be a valid date.',
            'start_datetime.after_or_equal' => 'Start datetime cannot be in the past.',

            'end_datetime.required' => 'End datetime is required.',
            'end_datetime.date' => 'End datetime must be a valid date.',
            'end_datetime.after' => 'End datetime must be after start datetime.',

            'recurrence_type.in' => 'Recurrence type must be none, daily, weekly, or monthly.',
            'recurrence_period.integer' => 'Recurrence period must be a number.',
            'recurrence_period.min' => 'Recurrence period must be at least 1.',

            'recurrence_until.date' => 'Recurrence until must be a valid date.',
            'recurrence_until.after' => 'Recurrence until must be after start datetime.',
        ];
    }
}
