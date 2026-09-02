<?php

namespace Modules\Core\Support\Contracts;

interface HasCustomPermissions
{
    public function permissionsArray(): array;
}
