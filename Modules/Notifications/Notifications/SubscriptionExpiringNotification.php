<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Subscriptions\Models\Subscription;

/**
 * `subscription_expiring` (§14, US-SUB-08) — the paid period ends soon.
 * Operational: mail/sms forced on (US-NOT-03).
 */
class SubscriptionExpiringNotification extends BaseNotification
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
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->getKey(),
            'current_period_end' => $this->subscription->current_period_end?->toIso8601String(),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications::messages.subscription.expiring_subject'))
            ->line(__('notifications::messages.subscription.expiring_body'));
    }

    public function toSms(User $notifiable): string
    {
        return __('notifications::messages.subscription.expiring_body');
    }
}
