<?php

namespace Modules\Subscriptions\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

class UpdateSubscriptionRequest extends BaseRequest
{
    // Authorization is checked in the controller via the SubscriptionPolicy.

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::in(['upgrade', 'downgrade', 'cancel']),
            ],
            'plan_id' => [
                'required_with:action:upgrade,action:downgrade',
                'integer',
                Rule::exists('subscription_plans', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => __('subscriptions::validation.action_required'),
            'action.in' => __('subscriptions::validation.action_invalid'),
            'plan_id.required_with' => __('subscriptions::validation.plan_required_for_action'),
            'plan_id.exists' => __('subscriptions::validation.plan_not_found'),
        ];
    }
}
