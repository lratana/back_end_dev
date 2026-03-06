<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Department;
use App\Models\Equipment;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $equipments = Equipment::all();

        foreach (Department::all() as $dept) {

            for ($i = 1; $i <= 3; $i++) {

                $room = Room::create([
                    'name' => "{$dept->code} Meeting Room {$i}",
                    'description' => "Main meeting room {$i} for {$dept->name}",
                    'location' => "Building A - Floor {$i}",
                    'capacity' => rand(5, 20),
                    'department_id' => $dept->id,
                ]);

                // attach random equipment
                $room->equipment()->attach(
                    $equipments->random(rand(2, 4))->pluck('id')
                );
            }
        }
    }
}
