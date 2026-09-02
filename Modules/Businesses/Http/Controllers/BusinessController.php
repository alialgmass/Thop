<?php

namespace Modules\Businesses\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Businesses\Http\Requests\StoreBusinessRequest;
use Modules\Businesses\Http\Requests\UpdateBusinessRequest;
use Modules\Businesses\Http\Resources\BusinessResource;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

class BusinessController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // BusinessPolicy::create encodes "business-type account with no profile
        // yet"; surface the specific reason as a 422 business-rule error rather
        // than a bare 403 so the client can route the user correctly.
        if (! $user->can('create', BusinessAccount::class)) {
            $alreadyHas = $user->requiresBusinessProfile();
            $message = $alreadyHas
                ? __('businesses::profile.already_exists')
                : __('businesses::profile.customer_forbidden');

            throw ExceptionResponse::instance($message, 422)
                ->setCustomBody([$alreadyHas ? 'user_id' : 'account_type' => [$message]]);
        }

        $business = $user->businessAccount()->create($request->validated());

        return $this
            ->apiCode(201)
            ->apiMessage(__('businesses::profile.profile_created'))
            ->apiBody(['business' => new BusinessResource($business)])
            ->apiResponse();
    }

    public function show(BusinessAccount $business): JsonResponse
    {
        return $this
            ->apiBody(['business' => new BusinessResource($business)])
            ->apiResponse();
    }

    public function update(UpdateBusinessRequest $request, BusinessAccount $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update($request->validated());

        return $this
            ->apiMessage(__('businesses::profile.profile_updated'))
            ->apiBody(['business' => new BusinessResource($business->refresh())])
            ->apiResponse();
    }
}
