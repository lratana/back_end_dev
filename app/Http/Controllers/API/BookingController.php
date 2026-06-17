<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use App\Services\MeetingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        $user = $request->user();

        if (!$user || !isset($user->level)) return false;

        return in_array(strtolower(trim($user->level)), [
            'admin',
            'super_admin'
        ]);
    }

    private function hasConflict(int $roomId, string $start, string $end, ?int $ignoreId = null): bool
    {
        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        return Booking::query()
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'approved', 'in_meeting'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();
    }

    // private function syncMeetingStatuses(): void
    // {
    //     $now = now();
    //     //
    //     // Approved meeting starts automatically
    //     Booking::query()
    //         ->where('status', 'approved')
    //         ->where('start_datetime', '<=', $now)
    //         ->where('end_datetime', '>', $now)
    //         ->update([
    //             'status' => 'in_meeting',
    //             'updated_at' => $now,
    //         ]);

    //     // Meeting ends automatically
    //     Booking::query()
    //         ->whereIn('status', ['approved', 'in_meeting'])
    //         ->where('end_datetime', '<=', $now)
    //         ->update([
    //             'status' => 'completed',
    //             'updated_at' => $now,
    //         ]);
    // }
    private function isPastBooking(Booking $booking): bool
    {
        return $booking->end_datetime && $booking->end_datetime->isPast();
    }
    private function weekdayMap(): array
    {
        return [
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
        ];
    }

    private function normalizeRecurrenceDays($days): array
    {
        if (empty($days)) {
            return [];
        }

        if (is_string($days)) {
            $days = explode(',', $days);
        }

        if (!is_array($days)) {
            return [];
        }

        return collect($days)
            ->map(fn($d) => strtolower(trim((string) $d)))
            ->filter(fn($d) => array_key_exists($d, $this->weekdayMap()))
            ->values()
            ->all();
    }

    // // Notification helpers
    // private function notifyAdmins(Booking $booking, string $title, string $message): void
    // {
    //     $admins = User::query()
    //         ->whereIn('level', ['admin', 'super_admin'])
    //         ->where('id', '!=', $booking->user_id)
    //         ->get();

    //     foreach ($admins as $admin) {
    //         $admin->notify(new BookingStatusNotification($booking, $title, $message));
    //     }
    // }

    // private function notifyBookingOwner(Booking $booking, string $title, string $message): void
    // {
    //     if ($booking->user) {
    //         $booking->user->notify(new BookingStatusNotification($booking, $title, $message));
    //     }
    // }

    private function makeOccurrencePayload(
        Booking $booking,
        Carbon $occurrenceStart,
        Carbon $occurrenceEnd,
        bool $generated = false
    ): array {
        return [
            'id' => $generated ? "{$booking->id}_" . $occurrenceStart->format('YmdHis') : $booking->id,
            'booking_id' => $booking->id,
            'room_id' => $booking->room_id,
            'user_id' => $booking->user_id,
            'start_datetime' => $occurrenceStart->toDateTimeString(),
            'end_datetime' => $occurrenceEnd->toDateTimeString(),
            'recurrence_type' => $booking->recurrence_type,
            'recurrence_days' => $booking->recurrence_days,
            'recurrence_period' => $booking->recurrence_period,
            'recurrence_until' => optional($booking->recurrence_until)->toDateString(),
            'meeting_title' => $booking->meeting_title,
            'meeting_chairman' => $booking->meeting_chairman,
            'snack_required' => $booking->snack_required,
            'snack_note' => $booking->snack_note,
            'technician_required' => $booking->technician_required,
            'technician_note' => $booking->technician_note,
            'status' => $booking->status,
            'cancel_reason' => $booking->cancel_reason,
            'reject_reason' => $booking->reject_reason,
            'room' => $booking->room,
            'user' => $booking->user,
            'is_generated' => $generated,
            // Add these
            'created_at' => optional($booking->created_at)->toDateTimeString(),
            'updated_at' => optional($booking->updated_at)->toDateTimeString(),
        ];
    }

    private function expandBookingOccurrences(Booking $booking, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $results = [];

        $baseStart = $booking->start_datetime->copy();
        $baseEnd = $booking->end_datetime->copy();
        $durationSeconds = $baseStart->diffInSeconds($baseEnd);

        $recurrenceType = $booking->recurrence_type ?? 'none';
        $period = (int) ($booking->recurrence_period ?: 1);
        if ($period < 1) {
            $period = 1;
        }

        $until = $booking->recurrence_until
            ? Carbon::parse($booking->recurrence_until)->endOfDay()
            : null;

        if ($recurrenceType === 'none') {
            if ($baseStart < $rangeEnd && $baseEnd > $rangeStart) {
                $results[] = $this->makeOccurrencePayload($booking, $baseStart, $baseEnd, false);
            }

            return $results;
        }

        if ($recurrenceType === 'daily') {
            $cursor = $baseStart->copy();

            while ($cursor < $rangeEnd) {
                if ($until && $cursor > $until) {
                    break;
                }

                $occurrenceStart = $cursor->copy();
                $occurrenceEnd = $occurrenceStart->copy()->addSeconds($durationSeconds);

                if ($occurrenceStart < $rangeEnd && $occurrenceEnd > $rangeStart) {
                    $results[] = $this->makeOccurrencePayload(
                        $booking,
                        $occurrenceStart,
                        $occurrenceEnd,
                        $occurrenceStart->ne($baseStart)
                    );
                }

                $cursor->addDays($period);
            }

            return $results;
        }

        if ($recurrenceType === 'weekly') {
            $days = $this->normalizeRecurrenceDays($booking->recurrence_days);

            if (empty($days)) {
                $days = [strtolower($baseStart->format('D'))];
                $days = array_map(fn($d) => match ($d) {
                    'sun' => 'sun',
                    'mon' => 'mon',
                    'tue' => 'tue',
                    'wed' => 'wed',
                    'thu' => 'thu',
                    'fri' => 'fri',
                    'sat' => 'sat',
                    default => 'mon',
                }, $days);
            }

            $weekdayMap = $this->weekdayMap();
            $weekStart = $baseStart->copy()->startOfWeek(Carbon::SUNDAY);
            $baseTime = [
                'hour' => $baseStart->hour,
                'minute' => $baseStart->minute,
                'second' => $baseStart->second,
            ];

            $weekIndex = 0;

            while (true) {
                $currentWeekStart = $weekStart->copy()->addWeeks($weekIndex * $period);

                if ($currentWeekStart >= $rangeEnd && !empty($results)) {
                    break;
                }

                foreach ($days as $dayCode) {
                    $weekday = $weekdayMap[$dayCode];

                    $occurrenceStart = $currentWeekStart->copy()
                        ->addDays($weekday)
                        ->setTime($baseTime['hour'], $baseTime['minute'], $baseTime['second']);

                    if ($occurrenceStart->lt($baseStart)) {
                        continue;
                    }

                    if ($until && $occurrenceStart->gt($until)) {
                        continue;
                    }

                    $occurrenceEnd = $occurrenceStart->copy()->addSeconds($durationSeconds);

                    if ($occurrenceStart < $rangeEnd && $occurrenceEnd > $rangeStart) {
                        $results[] = $this->makeOccurrencePayload(
                            $booking,
                            $occurrenceStart,
                            $occurrenceEnd,
                            $occurrenceStart->ne($baseStart)
                        );
                    }
                }

                if ($until && $currentWeekStart->gt($until)) {
                    break;
                }

                if ($currentWeekStart > $rangeEnd && !empty($results)) {
                    break;
                }

                $weekIndex++;
                if ($weekIndex > 500) {
                    break;
                }
            }

            usort($results, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));

            return $results;
        }

        if ($recurrenceType === 'monthly') {
            $cursor = $baseStart->copy();
            $dayOfMonth = (int) $baseStart->day;

            while ($cursor < $rangeEnd) {
                if ($until && $cursor > $until) {
                    break;
                }

                $occurrenceStart = $cursor->copy();
                $occurrenceEnd = $occurrenceStart->copy()->addSeconds($durationSeconds);

                if ($occurrenceStart < $rangeEnd && $occurrenceEnd > $rangeStart) {
                    $results[] = $this->makeOccurrencePayload(
                        $booking,
                        $occurrenceStart,
                        $occurrenceEnd,
                        $occurrenceStart->ne($baseStart)
                    );
                }

                $cursor = $cursor->copy()->addMonthsNoOverflow($period);

                if ((int) $cursor->day !== $dayOfMonth) {
                    $cursor->day = min($dayOfMonth, $cursor->daysInMonth);
                }
            }

            return $results;
        }

        return $results;
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_datetime' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_datetime'],
            'ignore_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ]);

        // ✅ NO Carbon::parse()
        // ✅ Frontend already sends UTC: Y-m-d H:i:s
        $start = $data['start_datetime'];
        $end = $data['end_datetime'];

        $hasConflict = $this->hasConflict(
            $data['room_id'],
            $start,
            $end,
            $data['ignore_id'] ?? null
        );

        return response()->json([
            'room_id' => $data['room_id'],
            'ignore_id' => $data['ignore_id'] ?? null,
            'available' => !$hasConflict,
            'message' => $hasConflict
                ? 'Room is already booked for this time'
                : 'Room is available',
            'debug' => [
                'start_datetime' => $start,
                'end_datetime' => $end,
            ],
        ]);
    }

    public function availableRooms(Request $request)
    {
        $data = $request->validate([
            'start_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_datetime' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_datetime'],
            'ignore_id' => ['nullable', 'integer'],
            'participants' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'string'],
        ]);

        // ✅ NO Carbon::parse()
        // ✅ Frontend already sends UTC: Y-m-d H:i:s
        $start = $data['start_datetime'];
        $end = $data['end_datetime'];

        $ignoreId = $data['ignore_id'] ?? null;
        $participants = (int) ($data['participants'] ?? 0);

        $equipmentNames = collect(explode(',', $data['equipment'] ?? ''))
            ->map(fn($name) => strtolower(trim($name)))
            ->filter()
            ->reject(fn($name) => $name === 'any')
            ->values();

        $rooms = Room::query()
            ->with(['department', 'equipment', 'images'])
            ->when(
                $participants > 0,
                fn($query) => $query->where('capacity', '>=', $participants)
            )
            ->whereDoesntHave('bookings', function ($query) use ($start, $end, $ignoreId) {
                $query->whereIn('status', [
                    'pending',
                    'approved',
                    'in_meeting',
                    'cancel_requested',
                ])
                    ->when(
                        $ignoreId,
                        fn($query) => $query->where('id', '!=', $ignoreId)
                    )
                    // ✅ Correct overlap check
                    // existing_start < requested_end
                    // existing_end > requested_start
                    ->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            });

        // ✅ Equipment logic: OR match
        if ($equipmentNames->isNotEmpty()) {
            $rooms->where(function ($query) use ($equipmentNames) {
                foreach ($equipmentNames as $name) {
                    $query->orWhereHas('equipment', function ($equipmentQuery) use ($name) {
                        $equipmentQuery->whereRaw('LOWER(name) LIKE ?', ["%{$name}%"]);
                    });
                }
            });
        }

        $rooms = $rooms->get();

        return response()->json([
            'data' => $rooms,
            'debug' => [
                'start_datetime' => $start,
                'end_datetime' => $end,
                'participants' => $participants,
                'equipment' => $equipmentNames->all(),
                'room_count' => $rooms->count(),
            ],
            'message' => $rooms->count()
                ? 'Available rooms fetched successfully'
                : 'No available rooms found',
        ]);
    }

    public function index(Request $request)
    {
        // ✅ Sync booking statuses before returning booking list
        // approved → in_meeting → completed
        app(MeetingService::class)->syncMeetingStatuses();

        $perPage = (int) $request->get('per_page', 10);
        $roomId = $request->integer('room_id');
        $status = $request->string('status')->toString();

        // ✅ DB stores UTC, so compare with UTC
        $now = now('UTC')->format('Y-m-d H:i:s');

        $query = Booking::with(['room', 'user'])
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '>=', $now)
            ->when($roomId, function ($query) use ($roomId) {
                $query->where('room_id', $roomId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            });

        if (!$this->isAdmin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json(
            $query->orderBy('start_datetime', 'asc')->paginate($perPage)
        );
    }

    public function show(Request $request, Booking $booking)
    {
        // ✅ Sync status before showing single booking
        app(MeetingService::class)->syncMeetingStatuses();

        // ✅ Refresh because sync may update this booking status
        $booking->refresh();

        if (!$this->isAdmin($request) && $booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            $booking->load(['room', 'user'])
        );
    }

    // public function show(Request $request, Booking $booking)
    // {
    //     if (!$this->isAdmin($request) && $booking->user_id !== $request->user()->id) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     return response()->json($booking->load(['room', 'user']));
    // }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;
        $data['recurrence_type'] = $data['recurrence_type'] ?? 'none';
        $data['recurrence_days'] = $this->normalizeRecurrenceDays($data['recurrence_days'] ?? null);
        $data['recurrence_period'] = $data['recurrence_period'] ?? null;
        $data['meeting_title'] = $data['meeting_title'] ?? null;
        $data['meeting_chairman'] = $data['meeting_chairman'] ?? null;
        $data['snack_required'] = $data['snack_required'] ?? false;
        $data['snack_note'] = $data['snack_note'] ?? null;
        $data['technician_required'] = $data['technician_required'] ?? false;
        $data['technician_note'] = $data['technician_note'] ?? null;
        $data['status'] = 'pending';
        $data['cancel_reason'] = null;
        $data['reject_reason'] = null;

        // ✅ FORCE UTC STRING (IMPORTANT FIX)
        $start = Carbon::parse($data['start_datetime']);
        $end   = Carbon::parse($data['end_datetime']);

        $startString = $start->toDateTimeString();
        $endString   = $end->toDateTimeString();

        // 🔥 FIX: conflict check uses STRING (safe)
        if ($this->hasConflict(
            $data['room_id'],
            $startString,
            $endString
        )) {
            return response()->json([
                'message' => 'Room is already booked for this time',
            ], 422);
        }

        // 🔥 FIX: store clean UTC strings
        $data['start_datetime'] = $startString;
        $data['end_datetime'] = $endString;

        $booking = Booking::create($data)->load(['room', 'user']);

        $this->notifyAdmins(
            $booking,
            'New Booking Created',
            'A new booking has been created and is waiting for approval.'
        );

        return response()->json($booking, 201);
    }
    public function addExtraTime(Request $request, Booking $booking)
    {
        app(MeetingService::class)->syncMeetingStatuses();
        $booking->refresh();
        $user = $request->user();

        if (!$user || $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
                'can_extend' => false,
            ], 403);
        }

        if (($booking->recurrence_type ?? 'none') !== 'none') {
            return response()->json([
                'message' => 'Recurring bookings cannot be extended individually yet',
                'can_extend' => false,
            ], 422);
        }

        $data = $request->validate([
            'extra_hours' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        // ✅ Always use UTC because DB stores UTC
        $now = now('UTC');

        $meetingStart = $booking->start_datetime;
        $oldEnd = $booking->end_datetime;

        if (!$meetingStart || !$oldEnd) {
            return response()->json([
                'message' => 'Booking start or end time is missing',
                'can_extend' => false,
            ], 422);
        }

        // ✅ Force UTC for comparison
        $meetingStart = $meetingStart->copy()->timezone('UTC');
        $oldEnd = $oldEnd->copy()->timezone('UTC');

        $status = strtolower(trim((string) $booking->status));

        /*
     * ✅ Auto-start fallback:
     * If scheduler did not change approved → in_meeting,
     * but now is already inside meeting time,
     * change it here immediately.
     */
        if (
            $status === 'approved' &&
            $meetingStart->lte($now) &&
            $oldEnd->gt($now)
        ) {
            $booking->status = 'in_meeting';


            $booking->save();
            $booking->refresh();

            $status = 'in_meeting';
            $meetingStart = $booking->start_datetime->copy()->timezone('UTC');
            $oldEnd = $booking->end_datetime->copy()->timezone('UTC');
        }

        if (!in_array($status, ['in_meeting'], true)) {
            return response()->json([
                'message' => 'Cannot add extra time because the meeting has not started yet.',
                'can_extend' => false,
                'debug' => [
                    'status' => $status,
                    'now_utc' => $now->format('Y-m-d H:i:s'),
                    'start_datetime' => $meetingStart->format('Y-m-d H:i:s'),
                    'end_datetime' => $oldEnd->format('Y-m-d H:i:s'),
                ],
            ], 422);
        }

        /*
     * Meeting can be extended only if:
     * start_datetime <= now < end_datetime
     */
        if ($now->lt($meetingStart)) {
            return response()->json([
                'message' => 'This meeting has not started yet',
                'can_extend' => false,
                'debug' => [
                    'now_utc' => $now->format('Y-m-d H:i:s'),
                    'meeting_start_utc' => $meetingStart->format('Y-m-d H:i:s'),
                    'meeting_end_utc' => $oldEnd->format('Y-m-d H:i:s'),
                    'status' => $booking->status,
                ],
            ], 422);
        }

        if ($now->gte($oldEnd)) {
            return response()->json([
                'message' => 'This meeting has already ended and cannot be extended',
                'can_extend' => false,
                'debug' => [
                    'now_utc' => $now->format('Y-m-d H:i:s'),
                    'end_datetime' => $oldEnd->format('Y-m-d H:i:s'),
                ],
            ], 422);
        }

        $extraHours = (int) $data['extra_hours'];
        $newEnd = $oldEnd->copy()->addHours($extraHours);

        /*
     * Check conflict only for the extra time:
     * old end time -> new end time
     */
        if ($this->hasConflict(
            $booking->room_id,
            $oldEnd->format('Y-m-d H:i:s'),
            $newEnd->format('Y-m-d H:i:s'),
            $booking->id
        )) {
            return response()->json([
                'message' => 'Cannot extend meeting because the room is already booked during the additional time',
                'can_extend' => false,
                'extension' => [
                    'extra_hours' => $extraHours,
                    'old_end_datetime' => $oldEnd->format('Y-m-d H:i:s'),
                    'requested_new_end_datetime' => $newEnd->format('Y-m-d H:i:s'),
                ],
            ], 422);
        }

        $booking->update([
            'end_datetime' => $newEnd->format('Y-m-d H:i:s'),
            'status' => 'in_meeting',
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $booking->refresh();
        $booking->load(['room', 'user']);

        $this->notifyAdmins(
            $booking,
            'Meeting Time Extended',
            "User {$booking->user->name} extended the current meeting in room {$booking->room->name} by {$extraHours} hour(s). No approval is required."
        );

        return response()->json([
            'message' => "Meeting extended successfully by {$extraHours} hour(s).",
            'can_extend' => true,
            'data' => $booking,
            'extension' => [
                'extra_hours' => $extraHours,
                'old_end_datetime' => $oldEnd->format('Y-m-d H:i:s'),
                'new_end_datetime' => $newEnd->format('Y-m-d H:i:s'),
                'status' => $booking->status,
                'requires_approval' => false,
            ],
            'debug' => [
                'now_utc' => $now->format('Y-m-d H:i:s'),
                'start_datetime' => $meetingStart->format('Y-m-d H:i:s'),
                'old_end_datetime' => $oldEnd->format('Y-m-d H:i:s'),
                'new_end_datetime' => $newEnd->format('Y-m-d H:i:s'),
            ],
        ]);
    }
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $isAdmin = $this->isAdmin($request);
        $isOwner = $booking->user_id === $request->user()->id;

        if (!$isAdmin && !$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be updated',
            ], 422);
        }

        $data = $request->validated();

        // Only admins can change status
        if (!$isAdmin && array_key_exists('status', $data)) {
            unset($data['status']);
        }

        // Normalize recurrence days
        if (array_key_exists('recurrence_days', $data)) {
            $data['recurrence_days'] = $this->normalizeRecurrenceDays($data['recurrence_days']);
        }

        // Handle approved bookings: only allow datetime updates
        if ($booking->status === 'approved' && !$isAdmin) {
            // Keep only start_datetime and end_datetime for update
            $data = array_intersect_key($data, array_flip(['start_datetime', 'end_datetime']));
        }

        $roomId = $data['room_id'] ?? $booking->room_id;
        $start = $data['start_datetime'] ?? $booking->start_datetime->toDateTimeString();
        $end = $data['end_datetime'] ?? $booking->end_datetime->toDateTimeString();

        if ($this->hasConflict($roomId, $start, $end, $booking->id)) {
            return response()->json([
                'message' => 'Room is already booked for this time',
            ], 422);
        }

        if (array_key_exists('snack_required', $data) && !$data['snack_required']) {
            $data['snack_note'] = null;
        }

        if (array_key_exists('technician_required', $data) && !$data['technician_required']) {
            $data['technician_note'] = null;
        }

        $booking->update($data);
        $booking->load(['room', 'user']);
        $this->notifyAdmins(
            $booking,
            'Booking Updated',
            "Booking #{$booking->id} has been updated. Please review the changes."
        );

        return response()->json($booking);
    }

    public function requestCancel(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot request cancellation',
            ], 422);
        }

        if ($booking->status !== 'approved' && $booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only approved and pending bookings can request cancellation',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $booking->update([
            'status' => 'cancel_requested',
            'cancel_reason' => $data['reason'],
        ]);

        $booking->load(['room', 'user']);

        $this->notifyAdmins(
            $booking,
            'Booking Cancel Request',
            'A booking cancellation request has been submitted. Reason: ' . $data['reason']
        );

        return response()->json([
            'message' => 'Cancel request submitted successfully',
            'data' => $booking,
        ]);
    }

    public function approve(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be approved',
            ], 422);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be approved',
            ], 422);
        }

        if ($this->hasConflict(
            $booking->room_id,
            $booking->start_datetime->toDateTimeString(),
            $booking->end_datetime->toDateTimeString(),
            $booking->id
        )) {
            return response()->json([
                'message' => 'Room is already booked for this time',
            ], 422);
        }

        $now = now();

        $newStatus = $booking->start_datetime <= $now && $booking->end_datetime > $now
            ? 'in_meeting'
            : 'approved';

        $booking->update([
            'status' => $newStatus,
            'reject_reason' => null,
        ]);

        $booking->load(['room', 'user']);

        $this->notifyBookingOwner(
            $booking,
            'Booking Approved',
            'Your booking has been approved successfully.'
        );

        return response()->json([
            'message' => 'Booking approved successfully',
            'data' => $booking,
        ]);
    }

    public function reject(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be rejected',
            ], 422);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be rejected',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $booking->update([
            'status' => 'rejected',
            'reject_reason' => $data['reason'],
        ]);

        $booking->load(['room', 'user']);

        $this->notifyBookingOwner(
            $booking,
            'Booking Rejected',
            'Your booking has been rejected. Reason: ' . $data['reason']
        );

        return response()->json([
            'message' => 'Booking rejected successfully',
            'data' => $booking,
        ]);
    }

    public function confirmCancel(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot confirm cancellation',
            ], 422);
        }

        if ($booking->status !== 'cancel_requested') {
            return response()->json([
                'message' => 'This booking has no cancel request',
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        $booking->load(['room', 'user']);

        $this->notifyBookingOwner(
            $booking,
            'Booking Cancelled',
            'Your booking has been cancelled successfully.'
        );

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ]);
    }

    public function adminCancel(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be cancelled directly',
            ], 422);
        }

        if ($booking->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved bookings can be force cancelled',
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        $booking->load(['room', 'user']);

        $this->notifyBookingOwner(
            $booking,
            'Booking Cancelled by Admin',
            'Your booking has been cancelled by admin.'
        );

        return response()->json([
            'message' => 'Booking cancelled successfully by admin',
            'data' => $booking,
        ]);
    }

    public function destroy(Request $request, Booking $booking)
    {
        $isAdmin = $this->isAdmin($request);
        $isOwner = $booking->user_id === $request->user()->id;

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be deleted',
            ], 422);
        }

        if ($isAdmin) {
            if (!in_array($booking->status, ['pending', 'rejected', 'cancelled'], true)) {
                return response()->json([
                    'message' => 'Admin can only delete pending, rejected, or cancelled bookings',
                ], 422);
            }

            $booking->delete();

            return response()->json([
                'message' => 'Booking deleted successfully',
            ]);
        }

        if (!$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'You can only delete pending bookings',
            ], 422);
        }

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully',
        ]);
    }

    public function dashboard(Request $request)
    {
        // $this->syncMeetingStatuses();
        //error this line
        $now = now();
        $todayStart = now()->copy()->startOfDay();
        $todayEnd = now()->copy()->endOfDay();
        $weekStart = now()->copy()->startOfWeek();
        $weekEnd = now()->copy()->endOfWeek();
        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();

        $baseQuery = Booking::with(['room', 'user'])
            ->where('end_datetime', '>=', $now); // Exclude expired bookings

        if (!$this->isAdmin($request)) {
            $baseQuery->where('user_id', $request->user()->id);
        }

        $upcoming = (clone $baseQuery)
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('start_datetime')
            ->limit(10)
            ->get();

        $recent = (clone $baseQuery)
            ->orderByDesc('start_datetime')
            ->limit(10)
            ->get();

        $today = (clone $baseQuery)
            ->whereBetween('start_datetime', [
                $todayStart->toDateTimeString(),
                $todayEnd->toDateTimeString(),
            ])
            ->count();

        $week = (clone $baseQuery)
            ->whereBetween('start_datetime', [
                $weekStart->toDateTimeString(),
                $weekEnd->toDateTimeString(),
            ])
            ->count();

        $cancelled = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->whereBetween('start_datetime', [
                $monthStart->toDateTimeString(),
                $monthEnd->toDateTimeString(),
            ])
            ->count();

        $pending = (clone $baseQuery)->where('status', 'pending')->count();
        $approved = (clone $baseQuery)->where('status', 'approved')->count();
        $rejected = (clone $baseQuery)->where('status', 'rejected')->count();
        $cancelRequested = (clone $baseQuery)->where('status', 'cancel_requested')->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();

        return response()->json([
            'upcoming' => $upcoming,
            'recent' => $recent,
            'today' => $today,
            'week' => $week,
            'cancelled' => $cancelled,
            'upcoming_count' => $upcoming->count(),
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'cancel_requested' => $cancelRequested,
            'completed' => $completed,
        ]);
    }
    // Notification helpers xxxxxxx
    private function notifyAdmins(Booking $booking, string $title, string $message): void
    {
        $admins = User::query()
            ->whereIn('level', ['admin', 'super_admin'])
            ->where('id', '!=', $booking->user_id)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new BookingStatusNotification($booking, $title, $message));
        }

        app(TelegramService::class)->sendBookingAlert($booking, $title, $message);
    }

    private function notifyBookingOwner(Booking $booking, string $title, string $message): void
    {
        if ($booking->user) {
            $booking->user->notify(new BookingStatusNotification($booking, $title, $message));
        }

        app(TelegramService::class)->sendBookingAlert($booking, $title, $message);
    }

    public function calendar(Request $request)
    {
        // $this->syncMeetingStatuses();
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $rangeStart = Carbon::parse($data['start']);
        $rangeEnd = Carbon::parse($data['end']);

        $query = Booking::with(['room', 'user']);

        if (!$this->isAdmin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        $bookings = $query->orderBy('start_datetime')->get();

        $items = collect();

        foreach ($bookings as $booking) {
            $occurrences = $this->expandBookingOccurrences($booking, $rangeStart, $rangeEnd);

            foreach ($occurrences as $occurrence) {
                $items->push($occurrence);
            }
        }

        return response()->json(
            $items->sortBy('start_datetime')->values()
        );
    }
}
