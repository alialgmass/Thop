<?php

namespace Modules\Subscriptions\Enums;

use Modules\Core\Support\Traits\EnumCommonTrait;

enum BillingCycle: string
{
    use EnumCommonTrait;

    case Monthly = 'monthly';
    case Annual = 'annual';

    public function labels(): array
    {
        return [
            self::Monthly->value => __('subscriptions::billing_cycle.monthly'),
            self::Annual->value => __('subscriptions::billing_cycle.annual'),
        ];
    }
}
