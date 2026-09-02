<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Concerns\IssuesApiToken;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;

class RegisterController extends Controller
{
    use IssuesApiToken;

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();

        if ($phone === null) {
            throw ExceptionResponse::instance(__('auth::otp.invalid_handoff'), 422)
                ->setCustomCode(4224)
                ->setCustomBody(['registration_token' => [__('auth::otp.invalid_handoff')]]);
        }

        if (User::query()->where('phone', $phone)->exists()) {
            throw ExceptionResponse::instance(__('auth::otp.already_registered'), 409)
                ->setCustomCode(4091)
                ->setCustomBody(['phone' => [__('auth::otp.already_registered')]]);
        }

        $user = User::query()->create([
            'phone' => $phone,
            'email' => $request->input('email'),
            'password' => $request->string('password')->value(),
            'language' => $request->language(),
            'status' => UserStatus::PendingTypeSelection,
            'account_type' => null,
        ]);

        return $this->tokenResponse($user, 201);
    }
}
