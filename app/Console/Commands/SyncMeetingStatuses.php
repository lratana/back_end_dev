<?php

namespace App\Console\Commands;

use App\Services\MeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMeetingStatuses extends Command
{
    protected $signature = 'meetings:sync';

    protected $description = 'Sync meeting statuses: approved → in_meeting → completed';

    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            Log::info('[MeetingSync] Started');

            $result = app(MeetingService::class)->syncMeetingStatuses();

            $duration = round(microtime(true) - $startTime, 3);

            Log::info('[MeetingSync] Completed', [
                'now_utc' => $result['now'],
                'started_count' => $result['started'],
                'completed_count' => $result['completed'],
                'duration_seconds' => $duration,
            ]);

            $this->info('Meeting sync completed successfully.');
            $this->line('Now UTC: ' . $result['now']);
            $this->line('Started: ' . $result['started']);
            $this->line('Completed: ' . $result['completed']);
            $this->line("Duration: {$duration}s");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('[MeetingSync] Failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->error('Meeting sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
