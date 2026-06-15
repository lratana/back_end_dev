<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class MeetingService
{
    public function syncMeetingStatuses(): void
    {
        // $now = Carbon::now();

        // Booking::where('status', 'approved')
        //     ->where('end_datetime', '<', $now)
        //     ->update([
        //         'status' => 'completed'
        //     ]);

        // Booking::where('status', 'ongoing')
        //     ->where('end_datetime', '<', $now)
        //     ->update([
        //         'status' => 'completed'
        //     ]);

        //     $now = now();

        // Approved meeting starts automatically
        Booking::query()
            ->where('status', 'approved')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->update([
                'status' => 'in_meeting',
                'updated_at' => $now,
            ]);

        // Meeting ends automatically
        Booking::query()
            ->whereIn('status', ['approved', 'in_meeting'])
            ->where('end_datetime', '<=', $now)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);
    }
}
