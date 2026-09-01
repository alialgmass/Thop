<?php

namespace Modules\Auth\Http\Concerns;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

trait ThrottlesByKey
{
    /**
     * Register one hit against the key, or raise a 429 when the per-minute
     * ceiling for that key has already been reached.
     */
    protected function hitOrThrottle(string $key, int $maxPerMinute): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxPerMinute)) {
            throw new ThrottleRequestsException(
                'Too many requests. Please wait '.RateLimiter::availableIn($key).' seconds and try again.',
            );
        }

        RateLimiter::hit($key, 60);
    }

    protected function clearThrottle(string $key): void
    {
        RateLimiter::clear($key);
    }
}
