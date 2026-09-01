<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Concerns\RendersApiErrors;
use Modules\Auth\Http\Requests\PasswordResetRequest;

class PasswordResetController extends Controller
{
    use RendersApiErrors;

    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();
        $user = $phone === null ? null : User::query()->where('phone', $phone)->first();

        if ($user === null) {
            return $this->apiError(__('auth::otp.invalid_handoff'), 'reset_token', 422);
        }

        $user->forceFill(['password' => $request->string('password')->value()])->save();

        // docs/CLAUDE.md rule 3 (security first): a credential change invalidates
        // every existing bearer token so a leaked one cannot outlive the reset.
        $user->tokens()->delete();

        return response()->json(['message' => __('auth::otp.password_updated')]);
    }
}
