<?php

namespace Modules\Notifications\Channels;

use App\Models\User;
use Modules\Notifications\Contracts\SmsSender;

/**
 * Discards SMS notifications — for environments with SMS disabled.
 */
class NullSmsSender implements SmsSender
{
    public function send(User $notifiable, string $message): void
    {
        //
    }
}
