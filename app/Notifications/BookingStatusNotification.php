<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    protected Booking $booking;
    protected string $title;
    protected string $message;

    public function __construct(Booking $booking, string $title, string $message)
    {
        $this->booking = $booking;
        $this->title = $title;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'user_id' => $this->booking->user_id,
            'room_name' => $this->booking->room?->name,
            'user_name' => $this->booking->user?->name,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->booking->status,
            'meeting_title' => $this->booking->meeting_title,
            'meeting_chairman' => $this->booking->meeting_chairman,
            'snack_required' => (bool) $this->booking->snack_required,
            'snack_note' => $this->booking->snack_note,
            'start_datetime' => optional($this->booking->start_datetime)->toDateTimeString(),
            'end_datetime' => optional($this->booking->end_datetime)->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
