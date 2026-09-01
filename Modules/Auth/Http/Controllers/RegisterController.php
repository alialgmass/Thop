<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Concerns\RendersApiErrors;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Concerns\IssuesApiToken;
use Modules\Auth\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
    use IssuesApiToken;
    use RendersApiErrors;

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();

        if ($phone === null) {
            return $this->apiError(__('auth::otp.invalid_handoff'), 'registration_token', 422);
        }

        if (User::query()->where('phone', $phone)->exists()) {
            return $this->apiError(__('auth::otp.already_registered'), 'phone', 409);
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
