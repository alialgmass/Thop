<?php

namespace Modules\Notifications\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Verification\Models\VerificationRequest;

/**
 * `verification_approved` / `verification_rejected` (§14, US-ADM-01). This is
 * account-critical — {@see self::operational()} forces mail + sms on
 * regardless of the user's push preference (US-NOT-03).
 */
class VerificationDecidedNotification extends BaseNotification
{
    public function __construct(
        public readonly VerificationRequest $verificationRequest,
        public readonly bool $approved,
        public readonly ?string $reason = null,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Verification;
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
            'type' => $this->approved ? 'verification_approved' : 'verification_rejected',
            'verification_request_id' => $this->verificationRequest->getKey(),
            'reason' => $this->reason,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject(__('notifications::messages.verification.'.($this->approved ? 'approved_subject' : 'rejected_subject')));

        return $this->approved
            ? $mail->line(__('notifications::messages.verification.approved_body'))
            : $mail->line(__('notifications::messages.verification.rejected_body', ['reason' => $this->reason ?? '']));
    }

    public function toSms(User $notifiable): string
    {
        return $this->approved
            ? __('notifications::messages.verification.approved_body')
            : __('notifications::messages.verification.rejected_body', ['reason' => $this->reason ?? '']);
    }
}
