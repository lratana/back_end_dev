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
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $perPage = (int) ($request->get('per_page', 10));

        $rooms = Room::query()
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->with(['equipment', 'images', 'department'])
            ->latest()
            ->paginate($perPage);

        return response()->json($rooms);
    }

    public function show(Room $room)
    {
        $room->load(['equipment', 'images', 'department']);

        return response()->json($room);
    }

    public function store(StoreRoomRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $room = new Room([
                'department_id' => $data['department_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'capacity' => $data['capacity'],
            ]);

            $room->created_by = $request->user()->id;

            if ($request->hasFile('thumbnail')) {
                $room->thumbnail_path = $request->file('thumbnail')->store('rooms/thumbnails', 'public');
            }

            $room->save();

            $equipmentIds = [];
            foreach (($data['equipment'] ?? []) as $name) {
                $eq = Equipment::firstOrCreate(['name' => $name]);
                $equipmentIds[] = $eq->id;
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

            return response()->json([
                'message' => 'Room created successfully',
                'data' => $room->load(['equipment', 'images', 'department']),
            ], 201);
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
                    $eq = Equipment::firstOrCreate(['name' => $name]);
                    $equipmentIds[] = $eq->id;
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

            return response()->json([
                'message' => 'Room updated successfully',
                'data' => $room->load(['equipment', 'images', 'department']),
            ]);
        });
    }

    public function destroy(Room $room)
    {
        // delete thumbnail file
        if ($room->thumbnail_path) {
            Storage::disk('public')->delete($room->thumbnail_path);
        }

        // delete room images files
        foreach ($room->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        // soft delete room
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }

    public function deleteImage(Room $room, RoomImage $image)
    {
        abort_unless($image->room_id === $room->id, 404);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }
}
