<?php

namespace Modules\Subscriptions\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Controllers\AuditLogController;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Subscriptions\Actions\ManageSubscriptionPlan;
use Modules\Subscriptions\Http\Requests\ApplyPlanToExistingRequest;
use Modules\Subscriptions\Http\Requests\StoreSubscriptionPlanRequest;
use Modules\Subscriptions\Http\Requests\UpdateSubscriptionPlanRequest;
use Modules\Subscriptions\Http\Resources\SubscriptionPlanResource;
use Modules\Subscriptions\Models\SubscriptionPlan;

/**
 * Admin CRUD + force-apply over subscription plans (US-SUB-05, US-ADM-04,
 * Phase 9 · T4). The gate is the `admin` route middleware, matching
 * {@see AuditLogController}'s convention.
 */
class AdminSubscriptionPlanController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ManageSubscriptionPlan $manage) {}

    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->with('entitlements')->orderBy('name')->get();

        return $this
            ->apiBody(['plans' => SubscriptionPlanResource::collection($plans)])
            ->apiResponse();
    }

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = $this->manage->create($request->validated(), $request->user());

        return $this
            ->apiMessage('Subscription plan created.')
            ->apiBody(['plan' => new SubscriptionPlanResource($plan->load('entitlements'))])
            ->apiResponse();
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $plan): JsonResponse
    {
        $updated = $this->manage->update($plan, $request->validated(), $request->user());

        return $this
            ->apiMessage('Subscription plan updated.')
            ->apiBody(['plan' => new SubscriptionPlanResource($updated->load('entitlements'))])
            ->apiResponse();
    }

    public function applyToExisting(ApplyPlanToExistingRequest $request, SubscriptionPlan $plan): JsonResponse
    {
        $count = $this->manage->applyToExisting($plan, $request->user());

        return $this
            ->apiMessage('Plan applied to existing subscriptions.')
            ->apiBody(['subscriptions_updated' => $count])
            ->apiResponse();
    }
}
