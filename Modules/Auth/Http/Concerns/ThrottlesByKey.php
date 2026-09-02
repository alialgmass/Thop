<?php

namespace Modules\Auth\Http\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

trait ThrottlesByKey
{
    /**
     * Register one hit against the key, or raise a 429 when the per-minute
     * ceiling for that key has already been reached.
     */
    protected function hitOrThrottle(string $key, int $maxPerMinute): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxPerMinute)) {
            $message = 'Too many requests. Please wait '.RateLimiter::availableIn($key).' seconds and try again.';

            throw ExceptionResponse::instance($message, 429)
                ->setCustomCode(4290)
                ->setCustomBody(['throttle' => [$message]]);
        }

        RateLimiter::hit($key, 60);
    }

    protected function clearThrottle(string $key): void
    {
        RateLimiter::clear($key);
    }
}
