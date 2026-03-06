<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'code'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
