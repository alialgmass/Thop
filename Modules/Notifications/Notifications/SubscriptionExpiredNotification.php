<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Subscriptions\Models\Subscription;

/**
 * `subscription_expired` (§14, US-SUB-08) — the subscription lapsed and the
 * seller's products are now hidden. Operational: mail/sms forced on.
 */
class SubscriptionExpiredNotification extends BaseNotification
{
    public function __construct(public readonly Subscription $subscription) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Subscription;
    }

    public function operational(): bool
    {
        return true;
    }

    /**
     * @return array<int, NotificationChannel>
     */
    public function matrixChannels(): array
    {
        return [
            NotificationChannel::Database,
            NotificationChannel::Push,
            NotificationChannel::Mail,
            NotificationChannel::Sms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'subscription_expired',
            'subscription_id' => $this->subscription->getKey(),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications::messages.subscription.expired_subject'))
            ->line(__('notifications::messages.subscription.expired_body'));
    }

    public function toSms(User $notifiable): string
    {
        return __('notifications::messages.subscription.expired_body');
    }
}
