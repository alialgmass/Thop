<?php

namespace Modules\Auth\Http\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\Support\Api\ApiResponse;

trait IssuesApiToken
{
    use ApiResponse;

    /**
     * Standard authenticated response: a fresh bearer token, the current-user
     * resource, and where the client should go next in onboarding.
     */
    protected function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return $this
            ->apiCode($status)
            ->apiBody([
                'token' => $user->createToken('api')->plainTextToken,
                'user' => new UserResource($user),
                'next_onboarding_step' => $user->nextOnboardingStep(),
            ])
            ->apiResponse();
    }
}
