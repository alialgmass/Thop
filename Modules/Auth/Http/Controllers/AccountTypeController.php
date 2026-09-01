<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Requests\AccountTypeRequest;
use Modules\Auth\Http\Resources\UserResource;

class AccountTypeController extends Controller
{
    public function __invoke(AccountTypeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasChosenAccountType()) {
            return response()->json([
                'message' => __('auth::otp.account_type_already_set'),
                'errors' => ['account_type' => [__('auth::otp.account_type_already_set')]],
            ], 409);
        }

        $user->forceFill([
            'account_type' => $request->accountType(),
            'status' => UserStatus::Active,
        ])->save();

        return response()->json([
            'message' => 'Account type set.',
            'user' => new UserResource($user->refresh()),
        ]);
    }
}
