<?php

namespace Modules\Admin\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;
use Modules\Subscriptions\Http\Requests\ApplyPlanToExistingRequest;

/**
 * `confirm` must be explicitly `true` — suspension immediately revokes the
 * account's access, so a bare POST with no body must not silently do
 * anything (Phase 9 · T8 acceptance criterion), same convention as
 * {@see ApplyPlanToExistingRequest}.
 */
class SuspendAccountRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm' => ['required', 'accepted'],
        ];
    }
}
