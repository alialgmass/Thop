<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Inquiries\Models\Rfq;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `new_rfq` (§14) — a buyer submitted a structured request for quotation
 * (US-INQ-02).
 */
class NewRfqNotification extends BaseNotification
{
    public function __construct(public readonly Rfq $rfq) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Rfq;
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
            'type' => 'new_rfq',
            'rfq_id' => $this->rfq->getKey(),
            'inquiry_id' => $this->rfq->inquiry_id,
            'product_id' => $this->rfq->product_id,
        ];
    }
}
