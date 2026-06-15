<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class MeetingService
{
    public function syncMeetingStatuses(): void
    {
        $now = Carbon::now();

        Booking::where('status', 'approved')
            ->where('end_datetime', '<', $now)
            ->update([
                'status' => 'completed'
            ]);

        Booking::where('status', 'ongoing')
            ->where('end_datetime', '<', $now)
            ->update([
                'status' => 'completed'
            ]);
    }
}
