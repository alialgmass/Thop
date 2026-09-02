<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\PasswordResetRequest;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

class PasswordResetController extends Controller
{
    use ApiResponse;

    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $phone = $request->verifiedPhone();
        $user = $phone === null ? null : User::query()->where('phone', $phone)->first();

        if ($user === null) {
            throw ExceptionResponse::instance(__('auth::otp.invalid_handoff'), 422)
                ->setCustomCode(4224)
                ->setCustomBody(['reset_token' => [__('auth::otp.invalid_handoff')]]);
        }

        $user->forceFill(['password' => $request->string('password')->value()])->save();

        // docs/CLAUDE.md rule 3 (security first): a credential change invalidates
        // every existing bearer token so a leaked one cannot outlive the reset.
        $user->tokens()->delete();

        return $this
            ->apiMessage(__('auth::otp.password_updated'))
            ->apiBody([])
            ->apiResponse();
    }
}
