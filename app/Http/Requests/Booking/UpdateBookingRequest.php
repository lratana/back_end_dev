<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => [
                'sometimes',
                'integer',
                'exists:rooms,id',
            ],

            'start_datetime' => [
                'sometimes',
                'date',
                'after_or_equal:now',
            ],

            'end_datetime' => [
                'sometimes',
                'date',
            ],

            'recurrence_type' => [
                'sometimes',
                'in:none,daily,weekly,monthly',
            ],

            'recurrence_days' => [
                'nullable',
                'array',
            ],

            'recurrence_days.*' => [
                'string',
                'in:mon,tue,wed,thu,fri,sat,sun',
            ],

            'recurrence_period' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'recurrence_until' => [
                'nullable',
                'date',
            ],

            'meeting_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meeting_chairman' => [
                'nullable',
                'string',
                'max:255',
            ],

            'snack_required' => [
                'nullable',
                'boolean',
            ],

            'snack_note' => [
                'nullable',
                'string',
            ],

            'status' => [
                'sometimes',
                'in:pending,approved,rejected,cancel_requested,cancelled,completed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'Selected room does not exist.',
            'start_datetime.date' => 'Start datetime must be a valid date.',
            'start_datetime.after_or_equal' => 'Start datetime cannot be in the past.',
            'end_datetime.date' => 'End datetime must be a valid date.',
            'recurrence_type.in' => 'Recurrence type must be none, daily, weekly, or monthly.',
            'recurrence_days.array' => 'Recurrence days must be an array.',
            'recurrence_days.*.in' => 'Each recurrence day must be one of: mon, tue, wed, thu, fri, sat, sun.',
            'recurrence_period.integer' => 'Recurrence period must be a number.',
            'recurrence_period.min' => 'Recurrence period must be at least 1.',
            'recurrence_until.date' => 'Recurrence until must be a valid date.',
            'meeting_title.string' => 'Meeting title must be text.',
            'meeting_chairman.string' => 'Meeting chairman must be text.',
            'snack_required.boolean' => 'Snack required must be true or false.',
            'snack_note.string' => 'Snack note must be text.',
            'status.in' => 'Invalid booking status.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_datetime');
            $end = $this->input('end_datetime');
            $until = $this->input('recurrence_until');
            $type = $this->input('recurrence_type');
            $days = $this->input('recurrence_days');

            if ($start && $end) {
                if (strtotime($end) <= strtotime($start)) {
                    $validator->errors()->add('end_datetime', 'End datetime must be after start datetime.');
                }
            }

            if ($start && $until) {
                if (strtotime($until) < strtotime(date('Y-m-d', strtotime($start)))) {
                    $validator->errors()->add('recurrence_until', 'Recurrence until must be on or after start date.');
                }
            }

            if ($type === 'weekly' && $this->exists('recurrence_days') && empty($days)) {
                $validator->errors()->add('recurrence_days', 'Weekly recurrence requires at least one recurrence day.');
            }
        });
    }
}
