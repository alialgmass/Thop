<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * The platform administrator role. Authorization policies check
 * `$user->hasRole('admin')`; fine-grained admin permissions arrive in Phase 9.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('admin', 'web');
    }
}
