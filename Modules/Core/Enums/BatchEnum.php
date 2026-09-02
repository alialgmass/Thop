<?php

namespace Modules\Core\Enums;

enum BatchEnum: string
{
    case CampaignBatch = 'campaign_batch';
    case WhatsappOfflineMessageBatch = 'whatsapp_offline_message_batch';
    case WhatsappResetBalance = 'whatsapp_reset_balance';

    public function name($value = null): string
    {
        return "{$this->value} $value";
    }
}
