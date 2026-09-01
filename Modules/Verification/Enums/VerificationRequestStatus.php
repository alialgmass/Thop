<?php

namespace Modules\Verification\Enums;

enum VerificationRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
