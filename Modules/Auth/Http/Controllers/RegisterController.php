<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();

        if ($phone === null) {
            return response()->json([
                'message' => __('auth::otp.invalid_handoff'),
                'errors' => ['registration_token' => [__('auth::otp.invalid_handoff')]],
            ], 422);
        }

        if (User::query()->where('phone', $phone)->exists()) {
            return response()->json([
                'message' => __('auth::otp.already_registered'),
                'errors' => ['phone' => [__('auth::otp.already_registered')]],
            ], 409);
        }

        $user = User::query()->create([
            'phone' => $phone,
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'language' => $request->language(),
            'status' => UserStatus::PendingTypeSelection,
            'account_type' => null,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }
}
