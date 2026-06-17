<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeetingService
{
    public function syncMeetingStatuses(): array
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s');

        $dbTime = DB::selectOne("
            SELECT
                UTC_TIMESTAMP() AS db_utc_now,
                NOW() AS db_now,
                @@session.time_zone AS session_time_zone,
                @@global.time_zone AS global_time_zone
        ");

        Log::info('[MeetingSync] Time Compare', [
            'php_now_utc' => $now,
            'db_utc_now' => $dbTime->db_utc_now ?? null,
            'db_now' => $dbTime->db_now ?? null,
            'session_time_zone' => $dbTime->session_time_zone ?? null,
            'global_time_zone' => $dbTime->global_time_zone ?? null,
        ]);

        $shouldStartBookings = Booking::query()
            ->select('id', 'status', 'start_datetime', 'end_datetime')
            ->where('status', 'approved')
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->get();

        Log::info('[MeetingSync] Should Start Bookings', [
            'count' => $shouldStartBookings->count(),
            'bookings' => $shouldStartBookings->toArray(),
        ]);

        $started = Booking::query()
            ->where('status', 'approved')
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>', $now)
            ->update([
                'status' => 'in_meeting',
                'updated_at' => $now,
            ]);

        $completed = Booking::query()
            ->whereIn('status', ['approved', 'in_meeting'])
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<=', $now)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);

        Log::info('[MeetingSync] Result', [
            'now' => $now,
            'started' => $started,
            'completed' => $completed,
        ]);

        return [
            'now' => $now,
            'db_utc_now' => $dbTime->db_utc_now ?? null,
            'db_now' => $dbTime->db_now ?? null,
            'started' => $started,
            'completed' => $completed,
        ];
    }
}
