<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Models\Conversation;

/**
 * A conversation as seen by a participant. `unread_count` is the caller's
 * own unread total on this thread (US-CHT-07) — set by the controller, which
 * knows who is asking.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inquiry_id' => $this->inquiry_id,
            'buyer_id' => $this->buyer_id,
            'seller_business_id' => $this->seller_business_id,
            'unread_count' => $this->whenHas('unread_count'),
            'last_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
