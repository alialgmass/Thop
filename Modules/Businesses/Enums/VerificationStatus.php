<?php

namespace Modules\Businesses\Enums;

/**
 * The verification standing of a business account — the single source of truth
 * for the "Verified" badge (US-ACC-05). Only {@see self::Verified} yields the
 * badge; any other value hides it immediately.
 */
enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}
