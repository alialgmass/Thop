<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `product_approved` / `product_rejected` (§14, US-ADM-02) — the outcome of
 * admin review of a seller's product.
 */
class ProductReviewedNotification extends BaseNotification
{
    public function __construct(
        public readonly Product $product,
        public readonly bool $approved,
        public readonly ?string $reason = null,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::ProductReview;
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
            'type' => $this->approved ? 'product_approved' : 'product_rejected',
            'product_id' => $this->product->getKey(),
            'reason' => $this->reason,
        ];
    }
}
