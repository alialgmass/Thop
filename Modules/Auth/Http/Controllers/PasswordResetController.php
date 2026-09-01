<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Http\Requests\PasswordResetRequest;

class PasswordResetController extends Controller
{
    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();

        $user = $phone === null
            ? null
            : User::query()->where('phone', $phone)->first();

        if ($user === null) {
            return response()->json([
                'message' => __('auth::otp.invalid_handoff'),
                'errors' => ['reset_token' => [__('auth::otp.invalid_handoff')]],
            ], 422);
        }

        $user->forceFill(['password' => $request->input('password')])->save();

        // Revoke every existing session token after a credential change.
        PersonalAccessToken::query()
            ->where('tokenable_type', $user->getMorphClass())
            ->where('tokenable_id', $user->getKey())
            ->delete();

        return response()->json(['message' => 'Password updated. Please log in.']);
    }
}
