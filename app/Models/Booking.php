<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',

        'start_datetime',
        'end_datetime',

        'recurrence_type',
        'recurrence_days',
        'recurrence_period',
        'recurrence_until',

        'meeting_title',
        'meeting_chairman',

        'snack_required',
        'snack_note',

        'status',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'recurrence_days' => 'array',
        'recurrence_until' => 'date',
        'recurrence_period' => 'integer',

        'snack_required' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
