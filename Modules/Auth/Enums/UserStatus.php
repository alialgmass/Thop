<?php

namespace Modules\Auth\Enums;

enum UserStatus: string
{
    case PendingTypeSelection = 'pending_type_selection';
    case Active = 'active';
    case Suspended = 'suspended';
}
