<?php

namespace Modules\Notifications\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `PUT /api/v1/notification-preferences/marketing` — the single opt-in flag
 * for marketing notifications, tracked separately from operational
 * preferences (US-NOT-04).
 */
class UpdateMarketingPreferenceRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
