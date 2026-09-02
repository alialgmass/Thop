<?php

namespace Modules\Core\Enums;

use Modules\Core\Support\Traits\EnumCommonTrait;

enum UserTypeEnum: string
{
    use EnumCommonTrait;

    case user = 'user';
    case system = 'system';

}
