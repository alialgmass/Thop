<?php

namespace Modules\Subscriptions\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;

class SubscribeRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where('is_active', true),
            ],
            'trial_ends_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => __('subscriptions::validation.plan_required'),
            'plan_id.exists' => __('subscriptions::validation.plan_not_found'),
        ];
    }
}
