<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Inquiries\Models\Inquiry;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `new_inquiry` (§14) — a buyer contacted the seller (US-INQ-01).
 */
class NewInquiryNotification extends BaseNotification
{
    public function __construct(public readonly Inquiry $inquiry) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Inquiry;
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
            'type' => 'new_inquiry',
            'inquiry_id' => $this->inquiry->getKey(),
            'product_id' => $this->inquiry->product_id,
            'buyer_id' => $this->inquiry->buyer_id,
        ];
    }
}
