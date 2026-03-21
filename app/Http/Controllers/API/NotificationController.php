<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $filter = $request->string('filter')->toString();

        $baseQuery = $request->user()->notifications()->latest();

        $query = clone $baseQuery;

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        if ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate($perPage);

        $allCount = $request->user()->notifications()->count();
        $unreadCount = $request->user()->unreadNotifications()->count();
        $readCount = $allCount - $unreadCount;

        return response()->json([
            'data' => $notifications->items(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'from' => $notifications->firstItem(),
            'to' => $notifications->lastItem(),
            'counts' => [
                'all' => $allCount,
                'unread' => $unreadCount,
                'read' => $readCount,
            ],
        ]);
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get();

        return response()->json([
            'count' => $notifications->count(),
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications
            ->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted.',
        ]);
    }
}
