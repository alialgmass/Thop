<?php

namespace App\Policies;

use App\Models\User;
use Modules\Sales\Models\Sale;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->tenant_id === $sale->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->tenant_id === $sale->tenant_id;
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->hasPermissionTo('products.manage');
    }
}
