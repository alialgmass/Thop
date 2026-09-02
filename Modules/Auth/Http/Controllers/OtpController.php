<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Exceptions\OtpDeliveryException;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Auth\Http\Requests\OtpRequestRequest;
use Modules\Auth\Http\Requests\OtpVerifyRequest;
use Modules\Auth\Services\OtpService;
use Modules\Auth\Support\HandoffToken;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

class OtpController extends Controller
{
    use ApiResponse;
    use ThrottlesByKey;

    public function __construct(private readonly OtpService $otp) {}

    public function request(OtpRequestRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-request:{$phone}", (int) config('auth.otp.throttle.request_per_minute', 3));

        $userExists = User::query()->where('phone', $phone)->exists();

        if ($purpose === OtpPurpose::Registration && $userExists) {
            throw ExceptionResponse::instance(__('auth::otp.already_registered'), 409)
                ->setCustomCode(4091)
                ->setCustomBody(['phone' => [__('auth::otp.already_registered')]]);
        }

        // Password reset for an unknown number: respond exactly as a real send
        // would, so registered numbers cannot be enumerated (spec Section 11).
        if ($purpose === OtpPurpose::PasswordReset && ! $userExists) {
            return $this
                ->apiMessage(__('auth::otp.code_sent'))
                ->apiResponse();
        }

        try {
            $this->otp->issue($phone, $purpose);
        } catch (OtpDeliveryException) {
            throw ExceptionResponse::instance(__('auth::otp.delivery_failed'), 503)
                ->setCustomCode(5031)
                ->setCustomBody(['phone' => [__('auth::otp.delivery_failed')]]);
        }

        return $this
            ->apiMessage(__('auth::otp.code_sent'))
            ->apiResponse();
    }

    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-verify:{$phone}", (int) config('auth.otp.throttle.verify_per_minute', 5));

        $this->otp->verify($phone, $request->string('code')->value(), $purpose);

        $tokenKey = $purpose === OtpPurpose::Registration ? 'registration_token' : 'reset_token';

        return $this
            ->apiMessage(__('auth::otp.verified'))
            ->apiBody([$tokenKey => HandoffToken::issue($phone, $purpose)])
            ->apiResponse();
    }
}
