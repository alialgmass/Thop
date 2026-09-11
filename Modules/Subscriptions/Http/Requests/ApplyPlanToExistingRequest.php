<?php

namespace Modules\Subscriptions\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `confirm` must be explicitly `true` — this action rewrites every active
 * subscriber's entitlement snapshot on the plan, so a bare POST with no body
 * must not silently do anything (Phase 9 · T4 acceptance criterion).
 */
class ApplyPlanToExistingRequest extends BaseRequest
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
