<?php

namespace Modules\Chat\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Chat\Events\MessageSent;
use Modules\Chat\Http\Requests\StoreMessageRequest;
use Modules\Chat\Http\Resources\MessageResource;
use Modules\Chat\Models\Conversation;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Messages on a conversation thread (US-CHT-03, 05, 06, 08).
 */
class MessageController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use ThrottlesByKey;

    /**
     * Paginated history — cursor-based so opening a long thread never loads
     * the whole thing (US-CHT-06). Oldest-first by default.
     */
    public function index(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $direction = $request->string('direction')->toString() === 'newer' ? 'asc' : 'desc';

        $payload = $conversation->messages()
            ->orderBy('id', $direction)
            ->cursorPaginate((int) config('chat.history_per_page', 30))
            ->withQueryString();

        $data = MessageResource::collection($payload)->toResponse($request)->getData(true);

        return $this
            ->apiMessage(__('chat::messages.messages_listed'))
            ->apiBody(['messages' => $data])
            ->apiResponse();
    }

    /**
     * Validate → persist to MySQL → fire {@see MessageSent} (which broadcasts
     * after commit, off the request path). A Pusher outage never fails or
     * delays this (US-CHT-03, 05, 11).
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        /** @var User $user */
        $user = $request->user();

        $this->hitOrThrottle(
            "chat:send:{$user->getKey()}",
            (int) config('chat.throttle.send_per_minute', 30),
        );

        $message = $conversation->messages()->create([
            'sender_id' => $user->getKey(),
            'body' => $request->string('body')->toString(),
        ]);

        // Keep the conversation's last-activity accurate for the list ordering.
        $conversation->touch();

        MessageSent::dispatch($message);

        return $this
            ->apiCode(201)
            ->apiMessage(__('chat::messages.message_sent'))
            ->apiBody(['message' => new MessageResource($message)])
            ->apiResponse();
    }
}
