<?php

namespace Modules\Core\Support\Traits;

use Spatie\Permission\Models\Permission;

trait ValidatesPermissions
{
    /**
     * If nobody has this permission, grant access to everyone
     * This avoids you from being locked out of your application.
     *
     * @param  string  $permission
     * @return bool
     */
    protected function nobodyHasAccess($permission)
    {
        if (! $requestedPermission = Permission::findByName($permission)) {
            return true;
        }

        return ! ($requestedPermission->hasDirectUsers($permission) || $requestedPermission->hasUsersViaRole($permission));
    }
}
