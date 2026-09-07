<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Inquiries\Models\Quotation;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `quotation_received` (§14) — the seller replied to an RFQ with a
 * time-bound offer (US-INQ-03). Recipient is the buyer.
 */
class QuotationReceivedNotification extends BaseNotification
{
    public function __construct(public readonly Quotation $quotation) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Quotation;
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function matrixChannels(): array
    {
        return [NotificationChannel::Database, NotificationChannel::Push];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'quotation_received',
            'quotation_id' => $this->quotation->getKey(),
            'rfq_id' => $this->quotation->rfq_id,
        ];
    }
}
