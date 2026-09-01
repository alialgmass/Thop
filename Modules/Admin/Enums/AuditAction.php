<?php

namespace Modules\Admin\Enums;

/**
 * The catalogue of administrative actions recorded in the audit log
 * (US-ADM-09). Each phase adds the actions it introduces.
 */
enum AuditAction: string
{
    case VerificationApproved = 'verification.approved';
    case VerificationRejected = 'verification.rejected';
}
