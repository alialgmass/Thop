<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Chat\Models\Message;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `new_message` (§14) — a chat message arrived (US-CHT-03). Sent alongside
 * the Phase 7 Pusher realtime push, not instead of it; recipient is the
 * participant who did not send it.
 */
class NewMessageNotification extends BaseNotification
{
    public function __construct(public readonly Message $message) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Message;
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function matrixChannels(): array
    {
        return [NotificationChannel::Database, NotificationChannel::Push];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'new_message',
            'message_id' => $this->message->getKey(),
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
        ];
    }
}
