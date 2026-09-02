<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Http\Concerns\IssuesApiToken;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;

class LoginController extends Controller
{
    use IssuesApiToken;
    use ThrottlesByKey;

    public function store(LoginRequest $request): JsonResponse
    {
        $this->hitOrThrottle($request->throttleKey(), (int) config('auth.otp.throttle.login_per_minute', 5));

        $phone = $request->phone();
        $user = $phone === null ? null : User::query()->where('phone', $phone)->first();

        if ($user === null || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ExceptionResponse::instance(__('auth::otp.login_failed'), 422)
                ->setCustomCode(4222)
                ->setCustomBody(['phone' => [__('auth::otp.login_failed')]]);
        }

        $this->clearThrottle($request->throttleKey());

        return $this->tokenResponse($user);
    }

    public function destroy(): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = auth()->user()->currentAccessToken();
        $token->delete();

        return $this
            ->apiMessage(__('auth::otp.logged_out'))
            ->apiBody([])
            ->apiResponse();
    }
}
