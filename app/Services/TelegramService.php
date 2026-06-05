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

    public function sendBookingAlert(Booking $booking, string $title, string $message): bool
    {
        $booking->loadMissing(['room', 'user']);

        $timezone = config('telegram.timezone', 'Asia/Phnom_Penh');

        $start = $booking->start_datetime
            ? Carbon::parse($booking->start_datetime)->timezone($timezone)->format('d M Y, h:i A')
            : '-';

        $end = $booking->end_datetime
            ? Carbon::parse($booking->end_datetime)->timezone($timezone)->format('d M Y, h:i A')
            : '-';

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
