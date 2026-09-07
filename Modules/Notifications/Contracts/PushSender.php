<?php

namespace Modules\Notifications\Contracts;

use App\Models\User;
use Modules\Notifications\Channels\LogPushSender;

/**
 * The seam for push delivery (SI-FR-04). The SRS names no vendor, so the
 * default binding is {@see LogPushSender};
 * a real FCM/APNs/OneSignal sender is a config-driven binding swap with no
 * change to any notification class.
 */
interface PushSender
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(User $notifiable, array $payload): void;
}
