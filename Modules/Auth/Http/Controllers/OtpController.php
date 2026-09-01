<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Concerns\RendersApiErrors;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Exceptions\OtpDeliveryException;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Auth\Http\Requests\OtpRequestRequest;
use Modules\Auth\Http\Requests\OtpVerifyRequest;
use Modules\Auth\Services\OtpService;
use Modules\Auth\Support\HandoffToken;

class OtpController extends Controller
{
    use RendersApiErrors;
    use ThrottlesByKey;

    public function __construct(private readonly OtpService $otp) {}

    public function request(OtpRequestRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-request:{$phone}", (int) config('auth.otp.throttle.request_per_minute', 3));

        $userExists = User::query()->where('phone', $phone)->exists();

        if ($purpose === OtpPurpose::Registration && $userExists) {
            return $this->apiError(__('auth::otp.already_registered'), 'phone', 409);
        }

        // Password reset for an unknown number: respond exactly as a real send
        // would, so registered numbers cannot be enumerated (spec Section 11).
        if ($purpose === OtpPurpose::PasswordReset && ! $userExists) {
            return response()->json(['message' => __('auth::otp.code_sent')]);
        }

        try {
            $this->otp->issue($phone, $purpose);
        } catch (OtpDeliveryException) {
            return $this->apiError(__('auth::otp.delivery_failed'), 'phone', 503);
        }

        return response()->json(['message' => __('auth::otp.code_sent')]);
    }

    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-verify:{$phone}", (int) config('auth.otp.throttle.verify_per_minute', 5));

        $this->otp->verify($phone, $request->string('code')->value(), $purpose);

        $tokenKey = $purpose === OtpPurpose::Registration ? 'registration_token' : 'reset_token';

        return response()->json([
            'message' => __('auth::otp.verified'),
            $tokenKey => HandoffToken::issue($phone, $purpose),
        ]);
    }
}
