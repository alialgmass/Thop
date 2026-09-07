<?php

namespace Modules\Notifications\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * `PUT /api/v1/notification-preferences` — upsert a batch of
 * (category, channel, enabled) rows (US-NOT-02). Disabling mail/sms on an
 * operational category is accepted and stored, but the resolver overrides it.
 */
class UpdateNotificationPreferencesRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.category' => ['required', Rule::enum(NotificationCategory::class)],
            'preferences.*.channel' => ['required', Rule::enum(NotificationChannel::class)],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }
}
