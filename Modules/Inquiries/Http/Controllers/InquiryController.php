<?php

namespace Modules\Inquiries\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Concerns\ThrottlesByKey;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Enums\InquiryParty;
use Modules\Inquiries\Enums\LeadStatus;
use Modules\Inquiries\Events\InquiryCreated;
use Modules\Inquiries\Http\Concerns\EnforcesInquiryLimit;
use Modules\Inquiries\Http\Concerns\GuardsProductOwnership;
use Modules\Inquiries\Http\Requests\StoreInquiryRequest;
use Modules\Inquiries\Http\Requests\UpdateInquiryLeadStatusRequest;
use Modules\Inquiries\Http\Resources\InquiryResource;
use Modules\Inquiries\Models\Inquiry;
use Modules\Subscriptions\Services\EntitlementService;

/**
 * Buyer-to-seller contact and the seller's Lead Management screen
 * (US-INQ-01, US-INQ-06/07, US-ANL-03).
 */
class InquiryController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use EnforcesInquiryLimit;
    use GuardsProductOwnership;
    use ThrottlesByKey;

    public function __construct(private readonly EntitlementService $entitlements) {}

    /**
     * `?role=buyer|seller` disambiguates when a user is both a buyer and a
     * seller-business owner; buyer is the default since every account type
     * that can reach this endpoint can send inquiries.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $role = InquiryParty::tryFrom((string) $request->query('role')) ?? InquiryParty::Buyer;

        $query = Inquiry::query()->latest('updated_at');

        if ($role === InquiryParty::Seller) {
            $business = $user->businessAccount;

            if (! $business) {
                return $this->apiBody(['inquiries' => ['data' => []]])->apiResponse();
            }

            $query->forSeller($business->getKey());
        } else {
            $query->forBuyer($user->getKey());
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->string('lead_status')->toString());
        }

        $payload = InquiryResource::collection($query->paginate())->toResponse($request)->getData(true);

        return $this->apiBody(['inquiries' => $payload])->apiResponse();
    }

    public function store(StoreInquiryRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('create', Inquiry::class);

        $this->hitOrThrottle("inquiries:create:{$user->getKey()}", (int) config('inquiries.throttle.create_per_minute', 10));

        $product = $request->filled('product_id') ? Product::find($request->integer('product_id')) : null;
        $sellerBusinessId = $request->integer('seller_business_id') ?: $product?->business_account_id;

        if ($product && $request->filled('seller_business_id')) {
            $this->assertProductBelongsToBusiness(
                $product,
                $request->integer('seller_business_id'),
                'inquiries::messages.seller_business_mismatch',
                'seller_business_id',
            );
        }

        $sellerBusiness = BusinessAccount::findOrFail($sellerBusinessId);

        $this->assertWithinInquiryLimit($this->entitlements, $sellerBusiness, InquiryParty::Seller);

        $buyerBusiness = $user->businessAccount;

        if ($buyerBusiness) {
            // Buyer-side limits are optional: no current plan defines
            // inquiry_limit for a buyer (Open Decision #2) — absent means
            // no-op, not blocked, unlike the seller side above.
            $this->assertWithinInquiryLimit($this->entitlements, $buyerBusiness, InquiryParty::Buyer, optional: true);
        }

        $inquiry = Inquiry::create([
            'buyer_id' => $user->getKey(),
            'seller_business_id' => $sellerBusiness->getKey(),
            'product_id' => $product?->getKey(),
            'message' => $request->string('message')->toString(),
            'lead_status' => LeadStatus::New,
        ]);

        $this->entitlements->incrementUsage($sellerBusiness, 'inquiry_count');

        if ($buyerBusiness) {
            $this->entitlements->incrementUsage($buyerBusiness, 'inquiry_count');
        }

        InquiryCreated::dispatch($inquiry);

        return $this
            ->apiCode(201)
            ->apiMessage(__('inquiries::messages.sent'))
            ->apiBody(['inquiry' => new InquiryResource($inquiry)])
            ->apiResponse();
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        $this->authorize('view', $inquiry);

        return $this->apiBody(['inquiry' => new InquiryResource($inquiry)])->apiResponse();
    }

    public function update(UpdateInquiryLeadStatusRequest $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('update', $inquiry);

        $inquiry->update(['lead_status' => $request->string('lead_status')->toString()]);

        return $this
            ->apiMessage(__('inquiries::messages.status_updated'))
            ->apiBody(['inquiry' => new InquiryResource($inquiry)])
            ->apiResponse();
    }
}
