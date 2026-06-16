<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeetingService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMeetingStatuses extends Command
{
    /**
     * Command signature
     */
    protected $signature = 'meetings:sync';

    /**
     * Command description
     */
    protected $description = 'Sync meeting statuses: approved → in_meeting → completed';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            Log::info('[MeetingSync] Started');

            app(MeetingService::class)->syncMeetingStatuses();

            $duration = round(microtime(true) - $startTime, 3);

            Log::info('[MeetingSync] Completed', [
                'duration_seconds' => $duration,
            ]);

            $this->info("Meeting sync completed successfully in {$duration}s");

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
