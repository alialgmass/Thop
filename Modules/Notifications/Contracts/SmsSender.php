<?php

namespace Modules\Notifications\Contracts;

use App\Models\User;
use Modules\Notifications\Channels\LogSmsSender;

/**
 * The seam for SMS delivery of account/financial notifications (US-NOT-03).
 * Default binding is {@see LogSmsSender};
 * a real gateway is a config-driven binding swap. Kept separate from the
 * Auth module's OTP delivery seam on purpose — different lifecycle, different
 * copy — though both follow the same log-driver pattern.
 */
interface SmsSender
{
    public function send(User $notifiable, string $message): void;
}
