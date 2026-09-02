<?php

namespace Modules\Core\Enums;

use Illuminate\Support\Facades\Queue;

enum QueueEnum: string
{
    case DEFAULT = 'default';
    case WHATSAPP = 'whatsapp';
    case SALLA_STORE = 'salla_store';
    case CAMPAIGN = 'campaign';
    case BACKUP = 'backup';
    case SALLA_CUSTOMER = 'salla_customer';
    case SALLA_CUSTOMER_SINGLE_STORE = 'salla_customer_single_store';
    case ZID_CUSTOMER_SINGLE_STORE = 'zid_customer_single_store';

    public function size(): int
    {
        return Queue::size($this->value);
    }

    /**
     * @return string
     */
    public static function salla(): array
    {
        return [
            QueueEnum::SALLA_CUSTOMER->value,
            QueueEnum::SALLA_STORE->value,
            QueueEnum::SALLA_CUSTOMER_SINGLE_STORE->value,
        ];
    }
}
