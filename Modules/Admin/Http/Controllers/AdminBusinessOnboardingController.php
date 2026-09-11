<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Actions\OnboardSupplier;
use Modules\Admin\Http\Requests\OnboardSupplierRequest;
use Modules\Businesses\Http\Resources\BusinessResource;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Admin assisted supplier onboarding (US-ADM-10, Phase 9 · T10, issue #39).
 * The gate is the `admin` route middleware, matching every other admin-only
 * controller.
 */
class AdminBusinessOnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OnboardSupplier $onboard) {}

    public function store(OnboardSupplierRequest $request): JsonResponse
    {
        $business = $this->onboard->handle([
            'phone' => $request->phone(),
            'account_type' => $request->string('account_type')->toString(),
            'password' => $request->string('password')->value(),
            'email' => $request->input('email'),
            'language' => $request->input('language'),
            ...$request->safe()->only(['company_name', 'activity', 'governorate_id', 'address', 'contact_person', 'contact_channels']),
        ], $request->user());

        return $this
            ->apiCode(201)
            ->apiMessage('Supplier onboarded.')
            ->apiBody(['business' => new BusinessResource($business->load('owner'))])
            ->apiResponse();
    }
}
