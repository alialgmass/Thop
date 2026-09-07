<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Http\Resources\MessageResource;
use Modules\Chat\Models\Message;

/**
 * Fired after a message is persisted (US-CHT-03).
 *
 * Implements {@see ShouldBroadcast} (queued via the framework's BroadcastEvent
 * job) with {@see self::$afterCommit} — the Pusher broadcast is only handed to
 * the queue once the surrounding transaction commits, and never runs on the
 * request path, so a Pusher or queue failure is fully isolated from the send:
 * the HTTP response is already returned with the persisted message
 * (US-CHT-05, US-CHT-11).
 *
 * Notifications (`new_message`, spec §14) attach in Phase 8 with no change
 * here.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Queue the broadcast only after the surrounding DB transaction commits.
     */
    public bool $afterCommit = true;

    public function __construct(public readonly Message $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new MessageResource($this->message))->resolve();
    }
}
