<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Verification\Models\VerificationRequest;

/**
 * `verification_submitted` (§14, US-ACC-04) — a business submitted its
 * documents. Recipient is the admin queue; in-app only.
 */
class VerificationSubmittedNotification extends BaseNotification
{
    public function __construct(public readonly VerificationRequest $verificationRequest) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Verification;
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
            'type' => 'verification_submitted',
            'verification_request_id' => $this->verificationRequest->getKey(),
            'business_account_id' => $this->verificationRequest->business_account_id,
        ];
    }
}
