<?php

namespace Modules\Chat\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * the whole thing (US-CHT-06). Newest-first by default (a client opening a
     * thread wants the latest page); `?direction=newer` walks forward in
     * ascending order instead.
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
     * Validate → persist to MySQL (in a transaction) → fire {@see MessageSent}
     * only after the commit. The broadcast leg is both queued and wrapped, so
     * nothing after the message is durably saved can fail or delay the send —
     * a Pusher outage is invisible to the caller (US-CHT-03, 05, 11).
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

        $message = DB::transaction(function () use ($conversation, $user, $request) {
            $message = $conversation->messages()->create([
                'sender_id' => $user->getKey(),
                'body' => $request->string('body')->toString(),
            ]);

            // Keep the conversation's last-activity accurate for list ordering.
            $conversation->touch();

            return $message;
        });

        try {
            MessageSent::dispatch($message);
        } catch (\Throwable $e) {
            // The message is already committed and is returned below / via the
            // history API regardless — realtime is best-effort (US-CHT-05).
            report($e);
        }

        return $this
            ->apiCode(201)
            ->apiMessage(__('chat::messages.message_sent'))
            ->apiBody(['message' => new MessageResource($message)])
            ->apiResponse();
    }
}
