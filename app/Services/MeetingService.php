<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class MeetingService
{
    private function syncMeetingStatuses(): void
    {
        $now = now();

        // 1. Start meeting automatically
        Booking::query()
            ->where('status', 'approved')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>=', $now)
            ->update([
                'status' => 'in_meeting',
                // 'updated_at' => $now,
            ]);

        // 2. End meeting automatically
        Booking::query()
            ->whereIn('status', ['approved', 'in_meeting'])
            ->where('end_datetime', '<', $now)
            ->update([
                'status' => 'completed',
                // 'updated_at' => $now,
            ]);
    }
}
