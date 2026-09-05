<?php

namespace Modules\Inquiries\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Catalog\Models\Product;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Enums\InquiryParty;
use Modules\Inquiries\Events\RfqCreated;
use Modules\Inquiries\Http\Concerns\EnforcesInquiryLimit;
use Modules\Inquiries\Http\Concerns\GuardsProductOwnership;
use Modules\Inquiries\Http\Requests\StoreRfqRequest;
use Modules\Inquiries\Http\Resources\RfqResource;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Rfq;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Structured requests-for-quotation on an inquiry thread (US-INQ-02).
 */
class RfqController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use EnforcesInquiryLimit;
    use GuardsProductOwnership;
    use ThrottlesByKey;

    public function __construct(private readonly EntitlementService $entitlements) {}

    public function store(StoreRfqRequest $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('create', [Rfq::class, $inquiry]);

        $this->hitOrThrottle("inquiries:create:{$request->user()->getKey()}", (int) config('inquiries.throttle.create_per_minute', 10));

        $product = Product::findOrFail($request->integer('product_id'));

        $this->assertProductBelongsToBusiness(
            $product,
            $inquiry->seller_business_id,
            'inquiries::messages.rfq_product_mismatch',
            'product_id',
        );

        // Re-check (not re-increment) the same inquiry_limit the inquiry
        // itself already consumed on creation — an RFQ is structured content
        // on an existing thread, not a second inquiry, so it must not count
        // twice against either side's usage counter.
        $this->assertWithinInquiryLimit($this->entitlements, $inquiry->sellerBusiness, InquiryParty::Seller);

        $buyerBusiness = $inquiry->buyer->businessAccount;

        if ($buyerBusiness) {
            $this->assertWithinInquiryLimit($this->entitlements, $buyerBusiness, InquiryParty::Buyer, optional: true);
        }

        $rfq = $inquiry->rfqs()->create([
            'product_id' => $product->getKey(),
            'quantity' => $request->integer('quantity'),
            'color_id' => $request->integer('color_id') ?: null,
            'needed_by_date' => $request->input('needed_by_date'),
        ]);

        RfqCreated::dispatch($rfq);

        return $this
            ->apiCode(201)
            ->apiMessage(__('inquiries::messages.rfq_created'))
            ->apiBody(['rfq' => new RfqResource($rfq->load(['quotations', 'product']))])
            ->apiResponse();
    }

    public function show(Rfq $rfq): JsonResponse
    {
        $this->authorize('view', $rfq);

        return $this->apiBody(['rfq' => new RfqResource($rfq->load(['quotations', 'product']))])->apiResponse();
    }
}
