<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\AccountType;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Concerns\RendersApiErrors;
use Modules\Auth\Http\Requests\AccountTypeRequest;
use Modules\Auth\Http\Resources\UserResource;

class AccountTypeController extends Controller
{
    use RendersApiErrors;

    /**
     * The selectable account types, with bilingual label + description (US-ACC-02).
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => AccountType::catalog()]);
    }

    public function store(AccountTypeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasChosenAccountType()) {
            return $this->apiError(__('auth::otp.account_type_already_set'), 'account_type', 409);
        }

        $user->forceFill([
            'account_type' => $request->accountType(),
            'status' => UserStatus::Active,
        ])->save();

        $user->refresh();

        return response()->json([
            'message' => __('auth::otp.account_type_set'),
            'user' => new UserResource($user),
            'next_onboarding_step' => $user->nextOnboardingStep(),
        ]);
    }
}
