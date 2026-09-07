<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Chat\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Chat broadcast channels
|--------------------------------------------------------------------------
|
| The private channel a conversation broadcasts on. This callback IS the
| enforcement point for realtime access (US-CHT-02): Pusher calls back into
| Laravel's /broadcasting/auth with the model-bound conversation, and we run
| the exact same ConversationPolicy the REST endpoints use — never trusting
| the client-declared channel name.
|
*/

Broadcast::channel(
    'conversation.{conversation}',
    fn (User $user, Conversation $conversation): bool => $user->can('view', $conversation),
);
