<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'location',
        'capacity',
        'thumbnail_path',
        'created_by',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'room_equipment', 'room_id', 'equipment_id');
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
