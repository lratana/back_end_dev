<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingReportController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        return $request->user()?->level === 'admin';
    }

    private function buildStatusStats($bookings): array
    {
        return [
            'total' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'approved' => $bookings->where('status', 'approved')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
            'cancel_requested' => $bookings->where('status', 'cancel_requested')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
        ];
    }

    private function baseQuery(Request $request, array $data)
    {
        $query = Booking::query()->with(['room', 'user']);

        if (!$this->isAdmin($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($this->isAdmin($request) && isset($data['user_id']) && $data['user_id'] !== null) {
            $query->where('user_id', (int) $data['user_id']);
        }

        if (isset($data['status']) && $data['status'] !== null && $data['status'] !== '') {
            $query->where('status', $data['status']);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:daily,monthly,yearly'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:pending,approved,rejected,cancel_requested,cancelled,completed'],
        ]);

        $type = $data['type'];
        $query = $this->baseQuery($request, $data);

        if ($type === 'daily') {
            $date = isset($data['date']) && $data['date']
                ? Carbon::parse($data['date'])
                : now();

            $query->whereDate('start_datetime', $date->toDateString());

            $bookings = $query->orderBy('start_datetime', 'asc')->get();

            $summary = $bookings
                ->groupBy(fn($item) => Carbon::parse($item->start_datetime)->format('H:00'))
                ->map(fn($items, $hour) => [
                    'label' => $hour,
                    'count' => $items->count(),
                ])
                ->values();

            return response()->json([
                'filters' => [
                    'type' => 'daily',
                    'date' => $date->toDateString(),
                    'user_id' => $this->isAdmin($request) ? ($data['user_id'] ?? null) : $request->user()->id,
                    'status' => $data['status'] ?? null,
                ],
                'type' => 'daily',
                'date' => $date->toDateString(),
                'stats' => $this->buildStatusStats($bookings),
                'summary' => $summary,
                'data' => $bookings,
            ]);
        }

        if ($type === 'monthly') {
            $month = isset($data['month']) && $data['month']
                ? (int) $data['month']
                : now()->month;

            $year = isset($data['year']) && $data['year']
                ? (int) $data['year']
                : now()->year;

            $query
                ->whereYear('start_datetime', $year)
                ->whereMonth('start_datetime', $month);

            $bookings = $query->orderBy('start_datetime', 'asc')->get();

            $summary = $bookings
                ->groupBy(fn($item) => Carbon::parse($item->start_datetime)->format('Y-m-d'))
                ->map(fn($items, $date) => [
                    'label' => $date,
                    'count' => $items->count(),
                ])
                ->values();

            return response()->json([
                'filters' => [
                    'type' => 'monthly',
                    'month' => $month,
                    'year' => $year,
                    'user_id' => $this->isAdmin($request) ? ($data['user_id'] ?? null) : $request->user()->id,
                    'status' => $data['status'] ?? null,
                ],
                'type' => 'monthly',
                'month' => $month,
                'year' => $year,
                'stats' => $this->buildStatusStats($bookings),
                'summary' => $summary,
                'data' => $bookings,
            ]);
        }

        $year = isset($data['year']) && $data['year']
            ? (int) $data['year']
            : now()->year;

        $query->whereYear('start_datetime', $year);

        $bookings = $query->orderBy('start_datetime', 'asc')->get();

        $summary = $bookings
            ->groupBy(fn($item) => Carbon::parse($item->start_datetime)->format('Y-m'))
            ->map(fn($items, $monthLabel) => [
                'label' => $monthLabel,
                'count' => $items->count(),
            ])
            ->values();

        return response()->json([
            'filters' => [
                'type' => 'yearly',
                'year' => $year,
                'user_id' => $this->isAdmin($request) ? ($data['user_id'] ?? null) : $request->user()->id,
                'status' => $data['status'] ?? null,
            ],
            'type' => 'yearly',
            'year' => $year,
            'stats' => $this->buildStatusStats($bookings),
            'summary' => $summary,
            'data' => $bookings,
        ]);
    }
}
