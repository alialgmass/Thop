<?php

namespace Modules\Chat\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Http\Resources\ConversationResource;
use Modules\Chat\Models\Conversation;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Models\Inquiry;

/**
 * The messaging thread bound to an inquiry (US-CHT-01, 02, 06, 07).
 */
class ConversationController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    /**
     * Idempotent open/create — a second call for the same inquiry returns the
     * existing conversation (US-CHT-01). Only an inquiry participant may open
     * it; the `view` ability on the inquiry is the same §8 matrix check.
     */
    public function store(Inquiry $inquiry): JsonResponse
    {
        $this->authorize('view', $inquiry);

        try {
            $conversation = Conversation::firstOrCreate(
                ['inquiry_id' => $inquiry->getKey()],
                [
                    'buyer_id' => $inquiry->buyer_id,
                    'seller_business_id' => $inquiry->seller_business_id,
                ],
            );
            $created = $conversation->wasRecentlyCreated;
        } catch (QueryException) {
            // Lost the race to the unique `inquiry_id` index — return the row
            // the other request just created.
            $conversation = Conversation::where('inquiry_id', $inquiry->getKey())->firstOrFail();
            $created = false;
        }

        return $this
            ->apiCode($created ? 201 : 200)
            ->apiMessage(__('chat::messages.conversation_opened'))
            ->apiBody(['conversation' => new ConversationResource($conversation)])
            ->apiResponse();
    }

    /**
     * The caller's conversations, newest activity first, each carrying the
     * caller's own unread count plus a top-level total (US-CHT-07, 21).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $conversations = Conversation::query()
            ->forParticipant($user)
            ->withUnreadCountFor($user)
            ->with('latestMessage')
            ->get()
            ->sortByDesc(fn (Conversation $c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values();

        return $this
            ->apiMessage(__('chat::messages.conversations_listed'))
            ->apiBody([
                'conversations' => ConversationResource::collection($conversations),
                'total_unread' => (int) $conversations->sum('unread_count'),
            ])
            ->apiResponse();
    }

    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->loadMissing('latestMessage');
        $conversation->unread_count = $conversation->unreadCountFor($request->user());

        return $this
            ->apiMessage(__('chat::messages.conversation_retrieved'))
            ->apiBody(['conversation' => new ConversationResource($conversation)])
            ->apiResponse();
    }

    /**
     * Mark the other party's messages on this thread as read and return the
     * refreshed counts (US-CHT-07).
     */
    public function read(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('markRead', $conversation);

        /** @var User $user */
        $user = $request->user();
        $conversation->markReadFor($user);

        $totalUnread = Conversation::query()
            ->forParticipant($user)
            ->withUnreadCountFor($user)
            ->get()
            ->sum('unread_count');

        return $this
            ->apiMessage(__('chat::messages.marked_read'))
            ->apiBody([
                'unread_count' => 0,
                'total_unread' => (int) $totalUnread,
            ])
            ->apiResponse();
    }
}
