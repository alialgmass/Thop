<?php

namespace Modules\Notifications\Enums;

use Modules\Notifications\Channels\PushChannel;
use Modules\Notifications\Channels\SmsChannel;

/**
 * Delivery channels. `Database` is the in-app feed and is never opt-out — the
 * badge is what a user silences, not the record (US-NOT-01). `Push` and `Sms`
 * route through this module's custom channels; `Mail` uses the framework mail
 * channel.
 */
enum NotificationChannel: string
{
    case Database = 'database';
    case Push = 'push';
    case Mail = 'mail';
    case Sms = 'sms';

    /**
     * The channel identifier Laravel's notification system expects in
     * `via()` — a driver name for built-ins, a class name for our custom ones.
     */
    public function driver(): string
    {
        return match ($this) {
            self::Database => 'database',
            self::Mail => 'mail',
            self::Push => PushChannel::class,
            self::Sms => SmsChannel::class,
        };
    }

    /**
     * Channels that account/financial ("must not be missed") notifications
     * force on regardless of preference (US-NOT-03).
     */
    public function isOperationalOverride(): bool
    {
        return in_array($this, [self::Mail, self::Sms], true);
    }
}
