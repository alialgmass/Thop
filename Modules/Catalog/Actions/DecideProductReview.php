<?php

namespace Modules\Catalog\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Events\ProductApproved;
use Modules\Catalog\Events\ProductHidden;
use Modules\Catalog\Events\ProductRejected;
use Modules\Catalog\Exceptions\ProductNotInReviewException;
use Modules\Catalog\Models\Product;

/**
 * The single place an admin decision on a product review is applied (US-SEL-11,
 * BR-ADM-01). Shared by the REST endpoint and the Filament panel so the state
 * guard, audit-log write, and domain events stay in one place — directly
 * modelled on DecideVerificationRequest.
 */
class DecideProductReview
{
    public function approve(Product $product, User $admin): Product
    {
        $this->assertAwaitingReview($product);

        DB::transaction(function () use ($product, $admin): void {
            $product->forceFill([
                'status' => ProductStatus::Published,
                'rejection_reason' => null,
            ])->save();

            AuditLog::record($admin, AuditAction::ProductApproved, $product, [
                'business_account_id' => $product->business_account_id,
            ]);
        });

        ProductApproved::dispatch($product);

        return $product;
    }

    public function reject(Product $product, User $admin, string $reason): Product
    {
        $this->assertAwaitingReview($product);

        DB::transaction(function () use ($product, $admin, $reason): void {
            $product->forceFill([
                'status' => ProductStatus::Rejected,
                'rejection_reason' => $reason,
            ])->save();

            AuditLog::record($admin, AuditAction::ProductRejected, $product, [
                'business_account_id' => $product->business_account_id,
                'reason' => $reason,
            ]);
        });

        ProductRejected::dispatch($product, $reason);

        return $product;
    }

    public function hide(Product $product, User $admin): Product
    {
        $this->assertPublished($product);

        DB::transaction(function () use ($product, $admin): void {
            $product->forceFill([
                'status' => ProductStatus::Hidden,
            ])->save();

            AuditLog::record($admin, AuditAction::ProductHidden, $product, [
                'business_account_id' => $product->business_account_id,
            ]);
        });

        ProductHidden::dispatch($product);

        return $product;
    }

    private function assertAwaitingReview(Product $product): void
    {
        if ($product->status !== ProductStatus::PendingReview) {
            throw new ProductNotInReviewException;
        }
    }

    private function assertPublished(Product $product): void
    {
        if ($product->status !== ProductStatus::Published) {
            throw new ProductNotInReviewException;
        }
    }
}
