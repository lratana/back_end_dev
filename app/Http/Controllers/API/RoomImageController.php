<?php

namespace App\Http\Controllers\API;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class RoomImageController extends Controller
{
    public function upload(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_primary' => 'nullable|boolean',
        ]);

        $path = $request->file('image')->store('rooms', 'public');

        if ($request->boolean('is_primary')) {
            RoomImage::where('room_id', $room->id)->update(['is_primary' => false]);
        }

        $img = RoomImage::create([
            'room_id' => $room->id,
            'image_path' => $path, // if your column is image_path
            'is_primary' => $request->boolean('is_primary'),
            'uploaded_by_user_id' => $request->user()->id ?? null,
        ]);

        return response([
            'message' => 'Image uploaded.',
            'data' => $img
        ], 201);
    }

    public function delete($imageId)
    {
        $img = RoomImage::findOrFail($imageId);
        Storage::disk('public')->delete($img->path);
        $img->delete();

        return response(['message' => 'Image deleted.'], 200);
    }
}
