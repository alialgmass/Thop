<?php

namespace Modules\Subscriptions\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseRequest;
use Modules\Subscriptions\Enums\BillingCycle;

class UpdateSubscriptionPlanRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => ['sometimes', Rule::in(['importer', 'wholesaler', 'retailer'])],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'billing_cycle' => ['sometimes', 'nullable', Rule::enum(BillingCycle::class)],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'entitlements' => ['sometimes', 'array'],
            'entitlements.*.key' => ['required_with:entitlements', 'string'],
            'entitlements.*.value' => ['required_with:entitlements', 'string'],
        ];
    }
}
