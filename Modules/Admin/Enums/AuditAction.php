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

    case ProductApproved = 'product.approved';
    case ProductRejected = 'product.rejected';
    case ProductHidden = 'product.hidden';
    case ProductEditsRequested = 'product.edits_requested';

    case TaxonomyCreated = 'taxonomy.created';
    case TaxonomyUpdated = 'taxonomy.updated';
    case TaxonomyDeactivated = 'taxonomy.deactivated';

    case SubscriptionPlanCreated = 'plan.created';
    case SubscriptionPlanUpdated = 'plan.updated';
    case SubscriptionPlanAppliedToExisting = 'plan.applied_to_existing';
}
