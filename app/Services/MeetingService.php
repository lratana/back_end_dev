<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class MeetingService
{
    /** @noinspection PhpUnused */
    // public function syncMeetingStatuses(): void
    // {
    //     $now = Carbon::now('UTC');

    //     // =========================
    //     // 1. START MEETINGS
    //     // =========================
    //     Booking::query()
    //         ->where('status', 'approved')
    //         ->whereNotNull('start_datetime')
    //         ->whereNotNull('end_datetime')
    //         ->where('start_datetime', '<=', $now)
    //         ->where('end_datetime', '>=', $now)
    //         ->update([
    //             'status' => 'in_meeting',
    //             'updated_at' => $now,
    //         ]);

    //     // =========================
    //     // 2. END MEETINGS
    //     // =========================
    //     Booking::query()
    //         ->whereIn('status', ['approved', 'in_meeting'])
    //         ->whereNotNull('end_datetime')
    //         ->where('end_datetime', '<', $now)
    //         ->update([
    //             'status' => 'completed',
    //             'updated_at' => $now,
    //         ]);
    // }
    /** @noinspection PhpUnused */
    public function syncMeetingStatuses(): void
    {
        $now = Carbon::now('UTC');

        // 🔥 START MEETING (safe window)
        Booking::query()
            ->where('status', 'approved')
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->update([
                'status' => 'in_meeting',
                'updated_at' => $now,
            ]);

        // 🔥 END MEETING
        Booking::query()
            ->whereIn('status', ['approved', 'in_meeting'])
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<=', $now)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);
    }
}
