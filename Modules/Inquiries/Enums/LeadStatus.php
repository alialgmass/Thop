<?php

namespace Modules\Inquiries\Enums;

/**
 * The seller's four-stage triage state for an inquiry (US-INQ-06/07). No
 * other statuses exist and no order between them is enforced — the SRS
 * gives exactly these four with no state-machine requirement.
 */
enum LeadStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';
    case NotCompleted = 'not_completed';
}
