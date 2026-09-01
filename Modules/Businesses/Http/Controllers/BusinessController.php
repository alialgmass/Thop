<?php

namespace Modules\Businesses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Businesses\Http\Requests\StoreBusinessRequest;
use Modules\Businesses\Http\Requests\UpdateBusinessRequest;
use Modules\Businesses\Http\Resources\BusinessResource;
use Modules\Businesses\Models\BusinessAccount;

class BusinessController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isBusinessAccount()) {
            return response()->json([
                'message' => __('businesses::profile.customer_forbidden'),
                'errors' => ['account_type' => [__('businesses::profile.customer_forbidden')]],
            ], 422);
        }

        if ($user->businessAccount()->exists()) {
            return response()->json([
                'message' => __('businesses::profile.already_exists'),
                'errors' => ['user_id' => [__('businesses::profile.already_exists')]],
            ], 422);
        }

        $business = $user->businessAccount()->create($request->validated());

        return (new BusinessResource($business))
            ->response()
            ->setStatusCode(201);
    }

    public function show(BusinessAccount $business): BusinessResource
    {
        return new BusinessResource($business);
    }

    public function update(UpdateBusinessRequest $request, BusinessAccount $business): BusinessResource
    {
        $this->authorize('update', $business);

        $business->update($request->validated());

        return new BusinessResource($business->refresh());
    }
}
