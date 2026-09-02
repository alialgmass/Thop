<?php

namespace Modules\Subscriptions\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Subscriptions\Http\Requests\SubscribeRequest;
use Modules\Subscriptions\Http\Requests\UpdateSubscriptionRequest;
use Modules\Subscriptions\Http\Resources\SubscriptionPlanResource;
use Modules\Subscriptions\Http\Resources\SubscriptionResource;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;
use Modules\Subscriptions\Services\EntitlementService;

class SubscriptionController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    /**
     * List available subscription plans filtered by account_type.
     */
    public function plans(): JsonResponse
    {
        $accountType = request()->query('account_type');

        $query = SubscriptionPlan::where('is_active', true)
            ->with('entitlements');

        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        $plans = $query->get();

        return $this->apiBody([
            'plans' => $plans->map(
                fn (SubscriptionPlan $plan) => (new SubscriptionPlanResource($plan))->toArray(request())
            )->values()->all(),
        ])
            ->apiMessage(__('subscriptions::messages.plans_retrieved'))
            ->apiCode(200)
            ->apiResponse();
    }

    /**
     * Subscribe to a plan (US-SUB-01, US-SUB-02).
     */
    public function store(SubscribeRequest $request, EntitlementService $entitlementService): JsonResponse
    {
        $user = $request->user();
        $business = $user->businessAccount;

        // Check for existing active subscription
        $existing = $entitlementService->getActiveSubscription($business);

        if ($existing) {
            return $this->apiCode(409)
                ->apiCustomCode(4091)
                ->apiMessage(__('subscriptions::messages.already_subscribed'))
                ->apiResponse();
        }

        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));

        // A business may only subscribe to a plan matching its own account type (P4).
        if (! in_array($user->account_type?->value, [null, $plan->account_type], true)) {
            return $this->apiCode(422)
                ->apiCustomCode(4222)
                ->apiMessage(__('subscriptions::messages.plan_type_mismatch'))
                ->apiResponse();
        }

        $subscription = Subscription::create([
            'business_account_id' => $business->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => 'active',
            'current_period_end' => $request->validated('trial_ends_at')
                ? null
                : now()->addMonth(),
            'trial_ends_at' => $request->validated('trial_ends_at'),
        ]);

        return $this->apiBody(['subscription' => new SubscriptionResource($subscription->load('plan.entitlements'))])
            ->apiMessage(__('subscriptions::messages.subscribed'))
            ->apiCode(201)
            ->apiResponse();
    }

    /**
     * Get a single subscription's details (plan, status, dates, entitlements).
     */
    public function show(Subscription $subscription): JsonResponse
    {
        try {
            $this->authorize('view', $subscription);
        } catch (AuthorizationException) {
            return $this->apiCode(403)
                ->apiCustomCode(4031)
                ->apiMessage(__('subscriptions::messages.unauthorized'))
                ->apiResponse();
        }

        return $this->apiBody([
            'subscription' => new SubscriptionResource($subscription->load('plan.entitlements')),
        ])
            ->apiMessage(__('subscriptions::messages.subscription_retrieved'))
            ->apiResponse();
    }

    /**
     * Get usage details for a subscription (US-SUB-04).
     */
    public function usage(Subscription $subscription, EntitlementService $entitlementService): JsonResponse
    {
        try {
            $this->authorize('view', $subscription);
        } catch (AuthorizationException) {
            return $this->apiCode(403)
                ->apiCustomCode(4031)
                ->apiMessage(__('subscriptions::messages.unauthorized'))
                ->apiResponse();
        }

        $plan = $subscription->plan;
        $entitlements = $plan->entitlements->mapWithKeys(fn ($e) => [$e->key => $e->value]);

        $usage = [];
        foreach ($entitlements as $key => $limit) {
            $counterKey = match ($key) {
                'product_limit' => 'product_count',
                'inquiry_limit' => 'inquiry_count',
                default => null,
            };

            $usage[$key] = [
                'limit' => $limit,
                'current' => $counterKey
                    ? $entitlementService->currentUsage($subscription->businessAccount, $counterKey)
                    : null,
            ];
        }

        return $this->apiBody([
            'subscription_id' => $subscription->getKey(),
            'plan' => $plan->name,
            'usage' => $usage,
            'current_period_end' => $subscription->current_period_end,
            'trial_ends_at' => $subscription->trial_ends_at,
        ])
            ->apiMessage(__('subscriptions::messages.usage_retrieved'))
            ->apiResponse();
    }

    /**
     * Upgrade/downgrade/cancel a subscription (US-SUB-05).
     *
     * - Upgrade: new entitlements apply immediately
     * - Downgrade: current entitlements remain until paid period ends (BR-SUB-02)
     * - Cancel: same as downgrade — effective at end of paid term
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        try {
            $this->authorize('update', $subscription);
        } catch (AuthorizationException) {
            return $this->apiCode(403)
                ->apiCustomCode(4031)
                ->apiMessage(__('subscriptions::messages.unauthorized'))
                ->apiResponse();
        }

        if (! $subscription->isActive()) {
            return $this->apiCode(422)
                ->apiCustomCode(4221)
                ->apiMessage(__('subscriptions::messages.subscription_not_active'))
                ->apiResponse();
        }

        $action = $request->validated('action');

        $result = match ($action) {
            'upgrade' => $this->handleUpgrade($subscription, $request->validated('plan_id')),
            'downgrade' => $this->handleDowngrade($subscription, $request->validated('plan_id')),
            'cancel' => $this->handleCancel($subscription),
            default => $subscription,
        };

        $responseSubscription = $result instanceof Subscription ? $result : $subscription;

        return $this->apiBody(['subscription' => new SubscriptionResource($responseSubscription->load('plan.entitlements'))])
            ->apiMessage(__('subscriptions::messages.subscription_updated'))
            ->apiResponse();
    }

    private function handleUpgrade(Subscription $subscription, int $newPlanId): Subscription
    {
        // Immediate upgrade: cancel old, create new (BR-SUB-02)
        $subscription->update(['status' => 'cancelled']);

        return Subscription::create([
            'business_account_id' => $subscription->business_account_id,
            'plan_id' => $newPlanId,
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);
    }

    private function handleDowngrade(Subscription $subscription, int $newPlanId): Subscription
    {
        // Downgrade: schedule for end of current paid period (BR-SUB-02).
        // Stored in notes to be applied when the current period ends — merging
        // into any existing pending flags so they are not clobbered.
        $notes = json_decode($subscription->notes ?? '', true) ?: [];
        $subscription->update([
            'notes' => json_encode(array_merge($notes, ['pending_plan_id' => $newPlanId])),
        ]);

        return $subscription;
    }

    private function handleCancel(Subscription $subscription): Subscription
    {
        // Cancel: effective at end of paid period (BR-SUB-02).
        // Mark for cancellation but keep active until period end.
        $notes = json_decode($subscription->notes ?? '', true) ?: [];
        $subscription->update([
            'notes' => json_encode(array_merge($notes, ['cancel_at_period_end' => true])),
        ]);

        return $subscription;
    }
}
