<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Conversation;

/**
 * The single authorization source for a conversation (US-CHT-02) — used by
 * the REST controllers AND the Pusher channel-auth callback in
 * routes/channels.php. Every ability resolves through
 * {@see Conversation::involvesUser()}, which delegates to the linked
 * inquiry's participant check, so there is exactly one place that decides
 * "may this user take part in this thread".
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->involvesUser($user);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->involvesUser($user);
    }

    public function markRead(User $user, Conversation $conversation): bool
    {
        return $conversation->involvesUser($user);
    }

    public function report(User $user, Conversation $conversation): bool
    {
        return $conversation->involvesUser($user);
    }
}
