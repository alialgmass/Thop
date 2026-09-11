<?php

namespace Modules\Catalog\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Events\ProductApproved;
use Modules\Catalog\Events\ProductEditsRequested;
use Modules\Catalog\Events\ProductHidden;
use Modules\Catalog\Events\ProductRejected;
use Modules\Catalog\Exceptions\ProductNotInReviewException;
use Modules\Catalog\Models\Product;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

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

        if (! $product->hasImage()) {
            throw ExceptionResponse::instance(__('catalog::messages.media_required_to_publish'), 422)
                ->setCustomBody(['media' => [__('catalog::messages.media_required_to_publish')]]);
        }

        $this->applyDecision($product, $admin, ProductStatus::Published, AuditAction::ProductApproved, null);

        ProductApproved::dispatch($product);

        return $product;
    }

    public function reject(Product $product, User $admin, string $reason): Product
    {
        $this->assertAwaitingReview($product);

        $this->applyDecision($product, $admin, ProductStatus::Rejected, AuditAction::ProductRejected, $reason);

        ProductRejected::dispatch($product, $reason);

        return $product;
    }

    /**
     * "Request edits": reject-with-reason that returns the product to draft
     * (instead of `rejected`) so the seller can revise and resubmit. Reuses
     * the same awaiting-review guard and {@see self::applyDecision()} as
     * {@see self::reject()} — the only difference is the resulting status,
     * audit action, and dispatched event.
     */
    public function requestEdits(Product $product, User $admin, string $reason): Product
    {
        $this->assertAwaitingReview($product);

        $this->applyDecision($product, $admin, ProductStatus::Draft, AuditAction::ProductEditsRequested, $reason);

        ProductEditsRequested::dispatch($product, $reason);

        return $product;
    }

    public function hide(Product $product, User $admin): Product
    {
        $this->assertPublished($product);

        $this->applyDecision($product, $admin, ProductStatus::Hidden, AuditAction::ProductHidden, null);

        ProductHidden::dispatch($product);

        return $product;
    }

    /**
     * The shared transaction shape every decision applies: set the resulting
     * status (+ reason, when there is one) and write the matching audit-log
     * row. Callers are responsible for their own state guard and event.
     */
    private function applyDecision(Product $product, User $admin, ProductStatus $status, AuditAction $action, ?string $reason): void
    {
        DB::transaction(function () use ($product, $admin, $status, $action, $reason): void {
            $product->forceFill([
                'status' => $status,
                'rejection_reason' => $reason,
            ])->save();

            AuditLog::record($admin, $action, $product, array_filter([
                'business_account_id' => $product->business_account_id,
                'reason' => $reason,
            ], fn ($value): bool => $value !== null));
        });
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
