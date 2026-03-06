<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        return $request->user()?->level === 'admin';
    }

    private function hasConflict(int $roomId, string $start, string $end, ?int $ignoreId = null): bool
    {
        return Booking::query()
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();
    }

    private function isPastBooking(Booking $booking): bool
    {
        return $booking->end_datetime && $booking->end_datetime->isPast();
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
        ]);

        $hasConflict = $this->hasConflict(
            $data['room_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );

        return response()->json([
            'room_id' => $data['room_id'],
            'available' => !$hasConflict,
            'message' => $hasConflict
                ? 'Room is already booked for this time'
                : 'Room is available',
        ]);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $roomId = $request->integer('room_id');
        $status = $request->string('status')->toString();

        $query = Booking::query()
            ->with(['room', 'user'])
            ->when($roomId, fn($q) => $q->where('room_id', $roomId))
            ->when($status, fn($q) => $q->where('status', $status));

        if (!$this->isAdmin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json(
            $query->orderByDesc('start_datetime')->paginate($perPage)
        );
    }

    public function show(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request) && $booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['room', 'user']));
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;
        $data['recurrence_type'] = $data['recurrence_type'] ?? 'none';
        $data['recurrence_period'] = $data['recurrence_period'] ?? null;
        $data['status'] = 'pending';

        if ($this->hasConflict(
            $data['room_id'],
            $data['start_datetime'],
            $data['end_datetime']
        )) {
            return response()->json([
                'message' => 'Room is already booked for this time'
            ], 422);
        }

        $booking = Booking::create($data);

        return response()->json($booking->load(['room', 'user']), 201);
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $isAdmin = $this->isAdmin($request);

        if (!$isAdmin && $booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be updated'
            ], 422);
        }

        if (!$isAdmin && $booking->status !== 'pending') {
            return response()->json([
                'message' => 'You can only update pending bookings'
            ], 422);
        }

        $data = $request->validated();

        if (!$isAdmin && array_key_exists('status', $data)) {
            unset($data['status']);
        }

        $roomId = $data['room_id'] ?? $booking->room_id;
        $start = $data['start_datetime'] ?? $booking->start_datetime->toDateTimeString();
        $end = $data['end_datetime'] ?? $booking->end_datetime->toDateTimeString();

        if ($this->hasConflict($roomId, $start, $end, $booking->id)) {
            return response()->json([
                'message' => 'Room is already booked for this time'
            ], 422);
        }

        $booking->update($data);

        return response()->json($booking->load(['room', 'user']));
    }

    public function requestCancel(Request $request, Booking $booking)
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot request cancellation'
            ], 422);
        }

        if (!in_array($booking->status, ['pending', 'approved'], true)) {
            return response()->json([
                'message' => 'This booking cannot be cancelled'
            ], 422);
        }

        $booking->update([
            'status' => 'cancel_requested'
        ]);

        return response()->json([
            'message' => 'Cancel request submitted successfully',
            'data' => $booking->load(['room', 'user'])
        ]);
    }

    public function approve(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be approved'
            ], 422);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be approved'
            ], 422);
        }

        if ($this->hasConflict(
            $booking->room_id,
            $booking->start_datetime->toDateTimeString(),
            $booking->end_datetime->toDateTimeString(),
            $booking->id
        )) {
            return response()->json([
                'message' => 'Room is already booked for this time'
            ], 422);
        }

        $booking->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Booking approved successfully',
            'data' => $booking->load(['room', 'user'])
        ]);
    }

    public function reject(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be rejected'
            ], 422);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending bookings can be rejected'
            ], 422);
        }

        $booking->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Booking rejected successfully',
            'data' => $booking->load(['room', 'user'])
        ]);
    }

    public function confirmCancel(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot confirm cancellation'
            ], 422);
        }

        if ($booking->status !== 'cancel_requested') {
            return response()->json([
                'message' => 'This booking has no cancel request'
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'data' => $booking->load(['room', 'user'])
        ]);
    }

    public function adminCancel(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->isPastBooking($booking)) {
            return response()->json([
                'message' => 'Past bookings cannot be cancelled directly'
            ], 422);
        }

        if (!in_array($booking->status, ['pending', 'approved', 'cancel_requested'], true)) {
            return response()->json([
                'message' => 'This booking cannot be cancelled directly'
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Booking cancelled successfully by admin',
            'data' => $booking->load(['room', 'user'])
        ]);
    }

    public function destroy(Request $request, Booking $booking)
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }

    public function dashboard(Request $request)
    {
        $now = now();

        $baseQuery = Booking::with(['room', 'user']);

        if (!$this->isAdmin($request)) {
            $baseQuery->where('user_id', $request->user()->id);
        }

        $upcoming = (clone $baseQuery)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_datetime', '>=', $now)
            ->orderBy('start_datetime')
            ->limit(10)
            ->get();

        $recent = (clone $baseQuery)
            ->orderByDesc('start_datetime')
            ->limit(10)
            ->get();

        return response()->json([
            'upcoming' => $upcoming,
            'recent' => $recent,
        ]);
    }

    public function calendar(Request $request)
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $query = Booking::with(['room', 'user'])
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where('start_datetime', '<', $data['end'])
            ->where('end_datetime', '>', $data['start']);

        if (!$this->isAdmin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json(
            $query->orderBy('start_datetime')->get()
        );
    }
}
