<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ use role, not level
        $users = User::where('level', 'user')->get();
        $rooms = Room::all();

        // fallback: if no user role=user, use any user
        if ($users->count() === 0) {
            $users = User::all();
        }

        foreach ($rooms as $room) {

            for ($i = 0; $i < 5; $i++) {

                $start = Carbon::now()
                    ->addDays(rand(1, 30))
                    ->setTime(rand(8, 15), 0, 0);

                $end = (clone $start)->addHours(rand(1, 3));

                $periods = ['morning', 'afternoon', 'full'];

                Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $users->random()->id,

                    'start_datetime' => $start,
                    'end_datetime' => $end,

                    'recurrence_type' => 'none',

                    // ✅ NEW: recurrence_period
                    'recurrence_period' => $periods[array_rand($periods)],

                    // keep null when recurrence_type = none
                    'recurrence_days' => null,
                    'recurrence_until' => null,

                    'status' => 'scheduled',
                ]);
            }
        }
    }
}
