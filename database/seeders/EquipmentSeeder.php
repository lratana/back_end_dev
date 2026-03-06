<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipment;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = [
            'Projector',
            'LED TV',
            'Whiteboard',
            'Microphone',
            'Video Conference System',
            'Air Conditioner',
            'Speaker System',
        ];

        foreach ($equipment as $item) {
            Equipment::updateOrCreate(['name' => $item]);
        }
    }
}
