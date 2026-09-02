<?php

namespace Modules\Admin\Models;

use App\Models\User;

/**
 * Administrator account. Shares the users table; distinguished by role.
 */
class Admin extends User
{
    protected $table = 'users';
}
