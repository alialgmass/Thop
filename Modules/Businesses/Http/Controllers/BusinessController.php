<?php

namespace Modules\Businesses\Http\Controllers;

use App\Http\Concerns\RendersApiErrors;
use App\Http\Concerns\ResolvesRequestUser;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Businesses\Http\Requests\StoreBusinessRequest;
use Modules\Businesses\Http\Requests\UpdateBusinessRequest;
use Modules\Businesses\Http\Resources\BusinessResource;
use Modules\Businesses\Models\BusinessAccount;

class BusinessController extends Controller
{
    use AuthorizesRequests;
    use RendersApiErrors;
    use ResolvesRequestUser;

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $user = $this->currentUser($request);

        // BusinessPolicy::create encodes "business-type account with no profile
        // yet"; surface the specific reason as a 422 business-rule error rather
        // than a bare 403 so the client can route the user correctly.
        if (! $user->can('create', BusinessAccount::class)) {
            return $user->requiresBusinessProfile()
                ? $this->apiError(__('businesses::profile.already_exists'), 'user_id', 422)
                : $this->apiError(__('businesses::profile.customer_forbidden'), 'account_type', 422);
        }

        $business = $user->businessAccount()->create($request->validated());

        return (new BusinessResource($business))->response()->setStatusCode(201);
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
