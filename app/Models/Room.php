<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'location',
        'capacity',
        'thumbnail_path',
        'created_by'
    ];
    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'room_equipment');
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
