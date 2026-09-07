<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Modules\Notifications\Contracts\PushSender;

/**
 * Discards push notifications — for environments with push disabled.
 */
class NullPushSender implements PushSender
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $notifiable, array $payload): void
    {
        //
    }
}
