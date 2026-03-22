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
            'room_id' => [
                'required',
                'integer',
                'exists:rooms,id',
            ],

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
                'array',
                'required_if:recurrence_type,weekly',
            ],

            'recurrence_days.*' => [
                'string',
                'in:mon,tue,wed,thu,fri,sat,sun',
            ],

            'recurrence_period' => [
                'nullable',
                'integer',
                'min:1',
                'required_unless:recurrence_type,none',
            ],

            'recurrence_until' => [
                'nullable',
                'date',
                'required_unless:recurrence_type,none',
            ],

            'meeting_title' => [
                'required',
                'string',
                'max:255',
            ],

            'meeting_chairman' => [
                'required',
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

            'technician_required' => [
                'nullable',
                'boolean',
            ],

            'technician_note' => [
                'nullable',
                'string',
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
            'recurrence_days.required_if' => 'Weekly recurrence requires at least one recurrence day.',
            'recurrence_days.array' => 'Recurrence days must be an array.',
            'recurrence_days.*.in' => 'Each recurrence day must be one of: mon, tue, wed, thu, fri, sat, sun.',
            'recurrence_period.required_unless' => 'Recurrence period is required for recurring bookings.',
            'recurrence_period.integer' => 'Recurrence period must be a number.',
            'recurrence_period.min' => 'Recurrence period must be at least 1.',
            'recurrence_until.required_unless' => 'Recurrence until is required for recurring bookings.',
            'recurrence_until.date' => 'Recurrence until must be a valid date.',

            'meeting_title.required' => 'Meeting title is required.',
            'meeting_title.string' => 'Meeting title must be text.',

            'meeting_chairman.required' => 'Meeting chairman is required.',
            'meeting_chairman.string' => 'Meeting chairman must be text.',

            'snack_required.boolean' => 'Snack required must be true or false.',
            'snack_note.string' => 'Snack note must be text.',

            'technician_required.boolean' => 'Technician required must be true or false.',
            'technician_note.string' => 'Technician note must be text.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('recurrence_type', 'none');
            $start = $this->input('start_datetime');
            $end = $this->input('end_datetime');
            $until = $this->input('recurrence_until');
            $days = $this->input('recurrence_days');

            if ($start && $end) {
                if (strtotime($end) <= strtotime($start)) {
                    $validator->errors()->add('end_datetime', 'End datetime must be after start datetime.');
                }
            }

            if ($type === 'weekly' && empty($days)) {
                $validator->errors()->add('recurrence_days', 'Weekly recurrence requires at least one recurrence day.');
            }

            if ($start && $until) {
                if (strtotime($until) < strtotime(date('Y-m-d', strtotime($start)))) {
                    $validator->errors()->add('recurrence_until', 'Recurrence until must be on or after start date.');
                }
            }

            if ($type === 'none') {
                if ($this->filled('recurrence_days')) {
                    $validator->errors()->add('recurrence_days', 'Recurrence days must be empty when recurrence type is none.');
                }

                if ($this->filled('recurrence_period')) {
                    $validator->errors()->add('recurrence_period', 'Recurrence period must be empty when recurrence type is none.');
                }

                if ($this->filled('recurrence_until')) {
                    $validator->errors()->add('recurrence_until', 'Recurrence until must be empty when recurrence type is none.');
                }
            }

            if ($this->boolean('snack_required') && !filled($this->input('snack_note'))) {
                $validator->errors()->add('snack_note', 'Snack note is required when snack is required.');
            }

            if ($this->boolean('technician_required') && !filled($this->input('technician_note'))) {
                $validator->errors()->add('technician_note', 'Technician note is required when technician is required.');
            }
        });
    }
}
