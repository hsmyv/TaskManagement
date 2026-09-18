<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 30), 100);

        $notifications = Notification::where('employee_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data'  => NotificationResource::collection($notifications->getCollection())->resolve($request),
            'unread'=> Notification::where('employee_id', $request->user()->id)->unread()->count(),
            'meta'  => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->employee_id === $request->user()->id, 403);
        $notification->markAsRead();
        return response()->json(['message' => 'Oxundu.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('employee_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);
        return response()->json(['message' => 'Hamısı oxundu.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('employee_id', $request->user()->id)->unread()->count();
        return response()->json(['count' => $count]);
    }
}
