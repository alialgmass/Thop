<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\AccountType;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Requests\AccountTypeRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

class AccountTypeController extends Controller
{
    use ApiResponse;

    /**
     * The selectable account types, with bilingual label + description (US-ACC-02).
     */
    public function index(): JsonResponse
    {
        return $this
            ->apiBody(['account_types' => AccountType::catalog()])
            ->apiResponse();
    }

    public function store(AccountTypeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasChosenAccountType()) {
            throw ExceptionResponse::instance(__('auth::otp.account_type_already_set'), 409)
                ->setCustomCode(4092)
                ->setCustomBody(['account_type' => [__('auth::otp.account_type_already_set')]]);
        }

        $user->forceFill([
            'account_type' => $request->accountType(),
            'status' => UserStatus::Active,
        ])->save();

        $user->refresh();

        return $this
            ->apiMessage(__('auth::otp.account_type_set'))
            ->apiBody([
                'user' => new UserResource($user),
                'next_onboarding_step' => $user->nextOnboardingStep(),
            ])
            ->apiResponse();
    }
}
