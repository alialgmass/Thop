<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Exceptions\OtpDeliveryException;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Auth\Http\Requests\OtpRequestRequest;
use Modules\Auth\Http\Requests\OtpVerifyRequest;
use Modules\Auth\Otp\OtpService;
use Modules\Auth\Support\HandoffToken;

class OtpController extends Controller
{
    use ThrottlesByKey;

    public function __construct(private readonly OtpService $otp) {}

    public function request(OtpRequestRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-request:{$phone}", (int) config('auth.otp.throttle.request_per_minute', 3));

        $userExists = User::query()->where('phone', $phone)->exists();

        if ($purpose === OtpPurpose::Registration && $userExists) {
            return response()->json([
                'message' => __('auth::otp.already_registered'),
                'errors' => ['phone' => [__('auth::otp.already_registered')]],
            ], 409);
        }

        if ($purpose === OtpPurpose::PasswordReset && ! $userExists) {
            // Do not disclose whether the number is registered.
            return response()->json(['message' => 'If that number has an account, a code has been sent.']);
        }

        try {
            $this->otp->issue($phone, $purpose);
        } catch (OtpDeliveryException) {
            return response()->json([
                'message' => __('auth::otp.delivery_failed'),
                'errors' => ['phone' => [__('auth::otp.delivery_failed')]],
            ], 503);
        }

        return response()->json(['message' => 'A verification code has been sent.']);
    }

    public function verify(OtpVerifyRequest $request): JsonResponse
    {
        $phone = $request->phone();
        $purpose = $request->purpose();

        $this->hitOrThrottle("auth:otp-verify:{$phone}", (int) config('auth.otp.throttle.verify_per_minute', 5));

        $this->otp->verify($phone, $request->input('code'), $purpose);

        $tokenKey = $purpose === OtpPurpose::Registration ? 'registration_token' : 'reset_token';

        return response()->json([
            'message' => 'Phone number verified.',
            $tokenKey => HandoffToken::issue($phone, $purpose),
        ]);
    }
}
