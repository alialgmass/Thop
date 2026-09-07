<?php

namespace Modules\Notifications\Listeners;

use App\Models\User;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Notification;
use Modules\Catalog\Events\ProductApproved;
use Modules\Catalog\Events\ProductRejected;
use Modules\Catalog\Events\ProductSubmitted;
use Modules\Chat\Events\MessageSent;
use Modules\Inquiries\Events\InquiryCreated;
use Modules\Inquiries\Events\QuotationReceived;
use Modules\Inquiries\Events\RfqCreated;
use Modules\Notifications\Notifications\NewInquiryNotification;
use Modules\Notifications\Notifications\NewMessageNotification;
use Modules\Notifications\Notifications\NewRfqNotification;
use Modules\Notifications\Notifications\ProductReviewedNotification;
use Modules\Notifications\Notifications\ProductSubmittedNotification;
use Modules\Notifications\Notifications\QuotationReceivedNotification;
use Modules\Notifications\Notifications\SubscriptionExpiredNotification;
use Modules\Notifications\Notifications\SubscriptionExpiringNotification;
use Modules\Notifications\Notifications\VerificationDecidedNotification;
use Modules\Notifications\Notifications\VerificationSubmittedNotification;
use Modules\Notifications\Support\AdminRecipients;
use Modules\Notifications\Support\NotificationChannelResolver;
use Modules\Subscriptions\Events\SubscriptionExpired;
use Modules\Subscriptions\Events\SubscriptionExpiring;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Events\VerificationSubmitted;

/**
 * The one place a domain event becomes a notification (US-NOT-19). Every
 * module keeps firing its own events untouched; this subscriber turns them
 * into Laravel notifications, and {@see NotificationChannelResolver}
 * decides the channels.
 */
class NotificationEventSubscriber
{
    public function subscribe(EventDispatcher $events): void
    {
        $events->listen(InquiryCreated::class, [self::class, 'onInquiryCreated']);
        $events->listen(RfqCreated::class, [self::class, 'onRfqCreated']);
        $events->listen(QuotationReceived::class, [self::class, 'onQuotationReceived']);
        $events->listen(MessageSent::class, [self::class, 'onMessageSent']);

        $events->listen(VerificationSubmitted::class, [self::class, 'onVerificationSubmitted']);
        $events->listen(VerificationApproved::class, [self::class, 'onVerificationApproved']);
        $events->listen(VerificationRejected::class, [self::class, 'onVerificationRejected']);

        $events->listen(ProductSubmitted::class, [self::class, 'onProductSubmitted']);
        $events->listen(ProductApproved::class, [self::class, 'onProductApproved']);
        $events->listen(ProductRejected::class, [self::class, 'onProductRejected']);

        $events->listen(SubscriptionExpiring::class, [self::class, 'onSubscriptionExpiring']);
        $events->listen(SubscriptionExpired::class, [self::class, 'onSubscriptionExpired']);
    }

    public function onInquiryCreated(InquiryCreated $event): void
    {
        $this->sendTo(
            $event->inquiry->sellerBusiness?->owner,
            new NewInquiryNotification($event->inquiry),
        );
    }

    public function onRfqCreated(RfqCreated $event): void
    {
        $this->sendTo(
            $event->rfq->inquiry?->sellerBusiness?->owner,
            new NewRfqNotification($event->rfq),
        );
    }

    public function onQuotationReceived(QuotationReceived $event): void
    {
        $this->sendTo(
            $event->quotation->rfq?->inquiry?->buyer,
            new QuotationReceivedNotification($event->quotation),
        );
    }

    public function onMessageSent(MessageSent $event): void
    {
        $this->sendTo(
            $event->message->conversation?->counterpartOf($event->message->sender),
            new NewMessageNotification($event->message),
        );
    }

    public function onVerificationSubmitted(VerificationSubmitted $event): void
    {
        Notification::send(
            AdminRecipients::all(),
            new VerificationSubmittedNotification($event->verificationRequest),
        );
    }

    public function onVerificationApproved(VerificationApproved $event): void
    {
        $this->sendTo(
            $event->verificationRequest->businessAccount?->owner,
            new VerificationDecidedNotification($event->verificationRequest, approved: true),
        );
    }

    public function onVerificationRejected(VerificationRejected $event): void
    {
        $this->sendTo(
            $event->verificationRequest->businessAccount?->owner,
            new VerificationDecidedNotification($event->verificationRequest, approved: false, reason: $event->reason),
        );
    }

    public function onProductSubmitted(ProductSubmitted $event): void
    {
        Notification::send(
            AdminRecipients::all(),
            new ProductSubmittedNotification($event->product),
        );
    }

    public function onProductApproved(ProductApproved $event): void
    {
        $this->sendTo(
            $event->product->businessAccount?->owner,
            new ProductReviewedNotification($event->product, approved: true),
        );
    }

    public function onProductRejected(ProductRejected $event): void
    {
        $this->sendTo(
            $event->product->businessAccount?->owner,
            new ProductReviewedNotification($event->product, approved: false, reason: $event->reason),
        );
    }

    public function onSubscriptionExpiring(SubscriptionExpiring $event): void
    {
        $this->sendTo(
            $event->subscription->businessAccount?->owner,
            new SubscriptionExpiringNotification($event->subscription),
        );
    }

    public function onSubscriptionExpired(SubscriptionExpired $event): void
    {
        $this->sendTo(
            $event->subscription->businessAccount?->owner,
            new SubscriptionExpiredNotification($event->subscription),
        );
    }

    private function sendTo(?User $user, object $notification): void
    {
        if ($user instanceof User) {
            $user->notify($notification);
        }
    }
}
