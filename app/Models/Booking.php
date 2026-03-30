<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

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

        'technician_required',
        'technician_note',

        'status',
        'cancel_reason',
        'reject_reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'recurrence_days' => 'array',
        'recurrence_until' => 'date',
        'recurrence_period' => 'integer',

        'snack_required' => 'boolean',
        'technician_required' => 'boolean',
        'deleted_at' => 'datetime',
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
