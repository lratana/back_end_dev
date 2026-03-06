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
        'recurrence_period',   // ✅ added
        'recurrence_until',

        'status'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',

        'recurrence_days' => 'array',
        'recurrence_until' => 'date',

        'recurrence_period' => 'string' // ✅ optional but clean
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
