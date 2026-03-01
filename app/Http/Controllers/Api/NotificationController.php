<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentAdded;
use App\Events\ChecklistToggled;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ChecklistResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\NotificationResource;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ── NotificationController ────────────────────────────────────────────────────

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('employee_id', $request->user()->id)
            ->latest()
            ->paginate(30);

        return response()->json([
            'data'  => NotificationResource::collection($notifications),
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
