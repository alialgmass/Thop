<?php

namespace Modules\Subscriptions\Enums;

use Modules\Core\Support\Traits\EnumCommonTrait;

enum SubscriptionStatus: string
{
    use EnumCommonTrait;

    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Restricted = 'restricted';

    public function labels(): array
    {
        return [
            self::Active->value => __('subscriptions::statuses.active'),
            self::Expired->value => __('subscriptions::statuses.expired'),
            self::Cancelled->value => __('subscriptions::statuses.cancelled'),
            self::Restricted->value => __('subscriptions::statuses.restricted'),
        ];
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Cancelled, self::Restricted]);
    }
}
