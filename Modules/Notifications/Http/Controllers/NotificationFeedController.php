<?php

namespace Modules\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Notifications\Http\Resources\NotificationResource;

/**
 * The in-app notification feed — the `database` channel's read surface
 * (US-NOT-01). Every query is scoped to the authenticated user by
 * Laravel's notifications relation, so a user never sees another's.
 */
class NotificationFeedController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = $request->boolean('unread')
            ? $user->unreadNotifications()
            : $user->notifications();

        $payload = NotificationResource::collection($query->paginate())
            ->toResponse($request)
            ->getData(true);

        return $this
            ->apiMessage(__('notifications::messages.notifications_listed'))
            ->apiBody(['notifications' => $payload])
            ->apiResponse();
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this
            ->apiMessage(__('notifications::messages.unread_count'))
            ->apiBody(['unread_count' => $request->user()->unreadNotifications()->count()])
            ->apiResponse();
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return $this
            ->apiMessage(__('notifications::messages.marked_read'))
            ->apiBody(['notification' => new NotificationResource($record)])
            ->apiResponse();
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this
            ->apiMessage(__('notifications::messages.all_marked_read'))
            ->apiBody(['unread_count' => 0])
            ->apiResponse();
    }
}
