<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreRoomRequest;
use App\Http\Requests\Booking\UpdateRoomRequest;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{

    private function applyCurrentStatusToRoom(Room $room, $now): Room
    {
        $currentBooking = $room->bookings->first(function ($booking) use ($now) {
            return $booking->start_datetime <= $now &&
                $booking->end_datetime > $now;
        });

        if ($currentBooking) {
            $room->current_status = 'in_meeting';
            $room->status = 'in_meeting';

            $room->current_status_label = 'In Meeting';
            $room->current_status_color = 'danger';
            $room->current_status_icon = 'event_busy';

            $room->current_booking_id = $currentBooking->id;
            $room->current_meeting_title = $currentBooking->meeting_title;
            $room->current_meeting_chairman = $currentBooking->meeting_chairman;
            $room->current_start_datetime = optional($currentBooking->start_datetime)->toDateTimeString();
            $room->current_end_datetime = optional($currentBooking->end_datetime)->toDateTimeString();
        } else {
            $room->current_status = 'available';
            $room->status = 'available';

            $room->current_status_label = 'Available';
            $room->current_status_color = 'success';
            $room->current_status_icon = 'check_circle';

            $room->current_booking_id = null;
            $room->current_meeting_title = null;
            $room->current_meeting_chairman = null;
            $room->current_start_datetime = null;
            $room->current_end_datetime = null;
        }

        unset($room->bookings);

        return $room;
    }

    // private function syncMeetingStatuses(): void
    // {
    //     $now = now();

    //     // Auto start approved meetings
    //     DB::table('bookings')
    //         ->where('status', 'approved')
    //         ->where('start_datetime', '<=', $now)
    //         ->where('end_datetime', '>', $now)
    //         ->update([
    //             'status' => 'in_meeting',
    //             'updated_at' => $now,
    //         ]);

    //     // Auto complete ended meetings
    //     DB::table('bookings')
    //         ->whereIn('status', ['approved', 'in_meeting'])
    //         ->where('end_datetime', '<=', $now)
    //         ->update([
    //             'status' => 'completed',
    //             'updated_at' => $now,
    //         ]);
    // }
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $perPage = (int) $request->get('per_page', 10);
        $now = now();

        $rooms = Room::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%");
                });
            })
            ->with([
                'department',
                'equipment',
                'images',

                // ✅ FIXED BOOKING FILTER
                'bookings' => function ($query) use ($now) {
                    $query->whereIn('status', ['pending', 'approved', 'in_meeting'])
                        ->where('end_datetime', '>=', $now)
                        ->orderBy('start_datetime');
                },
            ])
            ->latest()
            ->paginate($perPage);

        $rooms->getCollection()->transform(function ($room) use ($now) {
            return $this->applyCurrentStatusToRoom($room, $now);
        });

        return response()->json($rooms);
    }
    public function show(Room $room)
    {
        $now = now();

        $room->load([
            'department',
            'equipment',
            'images',

            // ✅ CLEAN BOOKING RULE
            'bookings' => function ($query) use ($now) {
                $query->whereIn('status', ['pending', 'approved', 'in_meeting'])
                    ->where('end_datetime', '>=', $now)
                    ->orderBy('start_datetime');
            },
        ]);

        return response()->json(
            $this->applyCurrentStatusToRoom($room, $now)
        );
    }

    public function store(StoreRoomRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $room = new Room();
            $room->department_id = $data['department_id'];
            $room->name = $data['name'];
            $room->description = $data['description'] ?? null;
            $room->location = $data['location'] ?? null;
            $room->capacity = $data['capacity'];
            $room->created_by = optional($request->user())->id;

            if ($request->hasFile('thumbnail')) {
                $room->thumbnail_path = $request->file('thumbnail')->store('rooms/thumbnails', 'public');
            }

            $room->save();

            $equipmentIds = [];
            foreach (($data['equipment'] ?? []) as $name) {
                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $equipment = Equipment::firstOrCreate(['name' => $name]);
                $equipmentIds[] = $equipment->id;
            }

            $room->equipment()->sync($equipmentIds);

            $files = $request->file('images', []);
            $order = 0;

            foreach ($files as $img) {
                $path = $img->store('rooms/images', 'public');

                RoomImage::create([
                    'room_id' => $room->id,
                    'image_path' => $path,
                    'sort_order' => $order++,
                ]);
            }

            return response()->json(
                $room->load(['department', 'equipment', 'images']),
                201
            );
        });
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        return DB::transaction(function () use ($request, $room) {
            $data = $request->validated();

            if (array_key_exists('department_id', $data)) {
                $room->department_id = $data['department_id'];
            }

            if (array_key_exists('name', $data)) {
                $room->name = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $room->description = $data['description'];
            }

            if (array_key_exists('location', $data)) {
                $room->location = $data['location'];
            }

            if (array_key_exists('capacity', $data)) {
                $room->capacity = $data['capacity'];
            }

            if ($request->hasFile('thumbnail')) {
                if ($room->thumbnail_path) {
                    Storage::disk('public')->delete($room->thumbnail_path);
                }

                $room->thumbnail_path = $request->file('thumbnail')->store('rooms/thumbnails', 'public');
            }

            $room->save();

            if (array_key_exists('equipment', $data)) {
                $equipmentIds = [];

                foreach (($data['equipment'] ?? []) as $name) {
                    $name = trim($name);

                    if ($name === '') {
                        continue;
                    }

                    $equipment = Equipment::firstOrCreate(['name' => $name]);
                    $equipmentIds[] = $equipment->id;
                }

                $room->equipment()->sync($equipmentIds);
            }

            $files = $request->file('images', []);
            if (count($files) > 0) {
                $maxOrder = $room->images()->max('sort_order');
                $order = is_null($maxOrder) ? 0 : ((int) $maxOrder + 1);

                foreach ($files as $img) {
                    $path = $img->store('rooms/images', 'public');

                    RoomImage::create([
                        'room_id' => $room->id,
                        'image_path' => $path,
                        'sort_order' => $order++,
                    ]);
                }
            }

            return response()->json(
                $room->load(['department', 'equipment', 'images'])
            );
        });
    }

    public function destroy(Room $room)
    {
        $room->loadMissing(['images', 'equipment']);

        if ($room->thumbnail_path) {
            Storage::disk('public')->delete($room->thumbnail_path);
        }

        foreach ($room->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }

            $image->delete();
        }

        $room->equipment()->detach();
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully',
        ]);
    }

    public function statusBoard(Request $request)
    {
        $now = now();

        // ✅ FIX 1: complete BOTH approved + in_meeting
        DB::table('bookings')
            ->whereIn('status', ['approved', 'in_meeting'])
            ->where('end_datetime', '<=', $now)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);

        $rooms = Room::query()
            ->with([
                'department',
                'bookings' => function ($query) use ($now) {

                    $query->whereIn('status', ['pending', 'approved', 'in_meeting'])
                        ->where('end_datetime', '>=', $now)
                        ->orderBy('start_datetime');
                },
            ])
            ->orderBy('name')
            ->get();

        $items = $rooms->map(function ($room) use ($now) {

            $currentBooking = $room->bookings->first(function ($booking) use ($now) {
                return $booking->start_datetime <= $now
                    && $booking->end_datetime > $now;
            });

            $nextBooking = $room->bookings->first(function ($booking) use ($now) {
                return $booking->start_datetime > $now;
            });

            if ($currentBooking) {
                return [
                    'room_id' => $room->id,
                    'room_name' => $room->name,
                    'department' => $room->department?->name,
                    'location' => $room->location,
                    'capacity' => $room->capacity,

                    'status' => 'occupied',

                    'booking_id' => $currentBooking->id,
                    'start_datetime' => $currentBooking->start_datetime,
                    'end_datetime' => $currentBooking->end_datetime,

                    'meeting_title' => $currentBooking->meeting_title,
                    'meeting_chairman' => $currentBooking->meeting_chairman,

                    'snack_required' => (bool) $currentBooking->snack_required,
                    'snack_note' => $currentBooking->snack_note,

                    'countdown_seconds' => max(
                        0,
                        $now->diffInSeconds($currentBooking->end_datetime, false)
                    ),
                ];
            }

            if ($nextBooking) {
                return [
                    'room_id' => $room->id,
                    'room_name' => $room->name,
                    'department' => $room->department?->name,
                    'location' => $room->location,
                    'capacity' => $room->capacity,

                    'status' => 'upcoming',

                    'booking_id' => $nextBooking->id,
                    'start_datetime' => $nextBooking->start_datetime,
                    'end_datetime' => $nextBooking->end_datetime,

                    'meeting_title' => $nextBooking->meeting_title,
                    'meeting_chairman' => $nextBooking->meeting_chairman,

                    'snack_required' => (bool) $nextBooking->snack_required,
                    'snack_note' => $nextBooking->snack_note,

                    'countdown_seconds' => max(
                        0,
                        $now->diffInSeconds($nextBooking->start_datetime, false)
                    ),
                ];
            }

            return [
                'room_id' => $room->id,
                'room_name' => $room->name,
                'department' => $room->department?->name,
                'location' => $room->location,
                'capacity' => $room->capacity,

                'status' => 'available',

                'booking_id' => null,
                'start_datetime' => null,
                'end_datetime' => null,

                'meeting_title' => null,
                'meeting_chairman' => null,

                'snack_required' => false,
                'snack_note' => null,

                'countdown_seconds' => null,
            ];
        });

        return response()->json($items->values());
    }

    public function deleteImage(Room $room, RoomImage $image)
    {
        abort_unless($image->room_id === $room->id, 404);

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }
}
