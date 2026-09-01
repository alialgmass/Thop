<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Http\Concerns\IssuesApiToken;
use Modules\Auth\Http\Concerns\RendersApiErrors;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Auth\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    use IssuesApiToken;
    use RendersApiErrors;
    use ThrottlesByKey;

    public function store(LoginRequest $request): JsonResponse
    {
        $this->hitOrThrottle($request->throttleKey(), (int) config('auth.otp.throttle.login_per_minute', 5));

        $phone = $request->phone();
        $user = $phone === null ? null : User::query()->where('phone', $phone)->first();

        if ($user === null || ! Hash::check($request->string('password')->value(), $user->password)) {
            return $this->apiError(__('auth::otp.login_failed'), 'phone', 422);
        }

        $this->clearThrottle($request->throttleKey());

        return $this->tokenResponse($user);
    }

    public function destroy(): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = auth()->user()->currentAccessToken();
        $token->delete();

        return response()->json(['message' => __('auth::otp.logged_out')]);
    }
}
