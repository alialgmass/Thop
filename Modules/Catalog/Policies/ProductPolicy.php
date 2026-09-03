<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Product;

/**
 * Authorization for the product lifecycle, per the Spec Section 8 matrix.
 *
 * Products:
 *   - create: business-account type, seller (importer now / retailer R3), not customer/wholesaler-only-as-buyer.
 *   - viewAny/view: own products (all statuses) or admin. Unavailable products are reachable by direct link.
 *   - update/delete/duplicate/updateStatus: owner only → foreign seller = 403.
 *   - review/hide: admin only.
 *
 * Ownership = product.business_account_id === $user->businessAccount->id.
 */
class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return in_array($ability, ['viewAny', 'view', 'review', 'hide'], true) ? true : null;
        }

        return null;
    }

    /**
     * A business-account owner may view their own products; admin may view all.
     */
    public function viewAny(User $user): bool
    {
        return $user->businessAccount()->exists();
    }

    /**
     * Owner or admin can view a single product. Unavailable products are
     * reachable by direct link (US-SEL-08).
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $product->business_account_id === $user->businessAccount?->getKey();
    }

    /**
     * Create: business-account type, seller (importer now / retailer R3).
     * Wholesaler-as-buyer is explicitly forbidden.
     */
    public function create(User $user): bool
    {
        $business = $user->businessAccount;

        if (! $business) {
            return false;
        }

        // Wholesaler-as-buyer is forbidden per the Authorization Matrix.
        return $user->account_type?->value !== 'wholesaler';
    }

    /**
     * Owner only — foreign seller = 403.
     */
    public function update(User $user, Product $product): bool
    {
        return $product->business_account_id === $user->businessAccount?->getKey();
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->business_account_id === $user->businessAccount?->getKey();
    }

    public function duplicate(User $user, Product $product): bool
    {
        return $product->business_account_id === $user->businessAccount?->getKey();
    }

    public function updateStatus(User $user, Product $product): bool
    {
        return $product->business_account_id === $user->businessAccount?->getKey();
    }

    /**
     * Admin-only: approve / reject (pending_review) or hide (published).
     */
    public function review(User $user, Product $product): bool
    {
        return $user->hasRole('admin');
    }

    public function hide(User $user, Product $product): bool
    {
        return $user->hasRole('admin');
    }
}
