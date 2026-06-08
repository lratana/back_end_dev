<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $message): bool
    {
        if (!config('telegram.enabled')) {
            return false;
        }

        $token = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');

        if (!$token || !$chatId) {
            Log::warning('Telegram bot token or chat ID is missing.');
            return false;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );

            if (!$response->successful()) {
                Log::error('Telegram alert failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram alert exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function formatBookingLocalTime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        /*
         * Booking datetime is Cambodia local time.
         *
         * Example DB value:
         * 2026-06-09 10:00:00
         *
         * Meaning:
         * 10:00 AM Cambodia time.
         *
         * Do NOT call ->timezone('Asia/Phnom_Penh') here,
         * because that will add +7 hours if Laravel treats it as UTC.
         */
        $text = str_replace('T', ' ', trim($value));
        $text = substr($text, 0, 19);

        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $text,
                'Asia/Phnom_Penh'
            )->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            Log::warning('Invalid booking datetime for Telegram alert', [
                'value' => $value,
                'message' => $e->getMessage(),
            ]);

            return '-';
        }
    }

    public function sendBookingAlert(Booking $booking, string $title, string $message): bool
    {
        $booking->loadMissing(['room', 'user']);

        /*
         * Use raw DB values to avoid Eloquent/Carbon timezone conversion.
         */
        $rawStart = $booking->getRawOriginal('start_datetime');
        $rawEnd = $booking->getRawOriginal('end_datetime');

        $start = $this->formatBookingLocalTime($rawStart);
        $end = $this->formatBookingLocalTime($rawEnd);

        $room = e($booking->room->name ?? 'Room #' . $booking->room_id);
        $user = e($booking->user->name ?? 'User #' . $booking->user_id);
        $meetingTitle = e($booking->meeting_title ?: '-');
        $chairman = e($booking->meeting_chairman ?: '-');
        $status = e($booking->status ?: '-');

        $text =
            "🔔 <b>" . e($title) . "</b>\n\n" .
            e($message) . "\n\n" .
            "🆔 Booking ID: <b>{$booking->id}</b>\n" .
            "🏢 Room: <b>{$room}</b>\n" .
            "👤 User: <b>{$user}</b>\n" .
            "📝 Meeting: <b>{$meetingTitle}</b>\n" .
            "👨‍💼 Chairman: <b>{$chairman}</b>\n" .
            "🕒 Start: <b>{$start}</b>\n" .
            "🕓 End: <b>{$end}</b>\n" .
            "📌 Status: <b>{$status}</b>";

        return $this->sendMessage($text);
    }
}
