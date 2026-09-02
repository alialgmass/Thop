<?php

namespace Modules\Core\Support\Traits;

trait AddToPermission
{
    public array $permissionArray = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'view_action_event',
    ];

    public array $extendPermissionArray = [];

    public function permissionArray(): array
    {
        return array_merge($this->permissionArray, $this->extendPermissionArray);
    }
}
