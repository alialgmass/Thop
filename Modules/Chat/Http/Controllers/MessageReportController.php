<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Chat\Http\Requests\ReportMessageRequest;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Chat\Providers\ChatServiceProvider;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Inquiries\Http\Resources\ReportResource;
use Modules\Inquiries\Models\Report;

/**
 * Either party flags a message as abusive (US-CHT-09). Reuses the Inquiries
 * `reports` table via the `message` morph alias (registered in
 * {@see ChatServiceProvider}). Only the durable
 * record is guaranteed here — the Admin dispute queue that reads it is
 * Phase 9 (US-ADM-08).
 */
class MessageReportController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function store(ReportMessageRequest $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('report', $conversation);

        abort_unless($message->conversation_id === $conversation->getKey(), 404);

        $report = Report::create([
            'reportable_type' => 'message',
            'reportable_id' => $message->getKey(),
            'reporter_id' => $request->user()->getKey(),
            'reason' => $request->string('reason')->toString(),
        ]);

        return $this
            ->apiCode(201)
            ->apiMessage(__('chat::messages.message_reported'))
            ->apiBody(['report' => new ReportResource($report)])
            ->apiResponse();
    }
}
