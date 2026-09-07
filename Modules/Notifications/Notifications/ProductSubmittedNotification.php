<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `product_submitted` (§14, US-SEL-02/11) — a product entered the review
 * queue. Recipient is the admin queue; in-app only.
 */
class ProductSubmittedNotification extends BaseNotification
{
    public function __construct(public readonly Product $product) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::ProductReview;
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function matrixChannels(): array
    {
        return [NotificationChannel::Database];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'product_submitted',
            'product_id' => $this->product->getKey(),
            'business_account_id' => $this->product->business_account_id,
        ];
    }
}
