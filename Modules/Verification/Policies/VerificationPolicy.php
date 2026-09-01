<?php

namespace Modules\Verification\Policies;

use App\Models\User;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Models\VerificationDocument;
use Modules\Verification\Models\VerificationRequest;

/**
 * Authorization for the verification flow, per the Spec Section 8 matrix:
 * a business owner may upload / submit / read the status / download their own
 * documents; only an admin may review (approve / reject) and read anyone's
 * documents. Verification documents are never reachable by another business.
 */
class VerificationPolicy
{
    private function owns(User $user, BusinessAccount $business): bool
    {
        return $business->user_id === $user->id;
    }

    public function upload(User $user, BusinessAccount $business): bool
    {
        return $this->owns($user, $business) && ! $business->isVerified();
    }

    public function submit(User $user, BusinessAccount $business): bool
    {
        return $this->owns($user, $business);
    }

    public function viewStatus(User $user, BusinessAccount $business): bool
    {
        return $this->owns($user, $business) || $user->hasRole('admin');
    }

    public function download(User $user, VerificationDocument $document): bool
    {
        return $user->hasRole('admin')
            || $document->verificationRequest->businessAccount->user_id === $user->id;
    }

    public function review(User $user, VerificationRequest $request): bool
    {
        return $user->hasRole('admin');
    }
}
