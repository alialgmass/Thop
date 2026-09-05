<?php

namespace Modules\Inquiries\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Events\QuotationReceived;
use Modules\Inquiries\Http\Requests\StoreQuotationRequest;
use Modules\Inquiries\Http\Resources\QuotationResource;
use Modules\Inquiries\Models\Quotation;
use Modules\Inquiries\Models\Rfq;

/**
 * The seller's time-bound reply to an RFQ (US-INQ-03).
 */
class QuotationController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function store(StoreQuotationRequest $request, Rfq $rfq): JsonResponse
    {
        $this->authorize('create', [Quotation::class, $rfq]);

        $quotation = $rfq->quotations()->create([
            'price' => $request->input('price'),
            'availability_note' => $request->input('availability_note'),
            'valid_until' => $request->input('valid_until'),
        ]);

        QuotationReceived::dispatch($quotation);

        return $this
            ->apiCode(201)
            ->apiMessage(__('inquiries::messages.quotation_sent'))
            ->apiBody(['quotation' => new QuotationResource($quotation)])
            ->apiResponse();
    }
}
