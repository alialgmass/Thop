<?php

namespace Modules\Chat\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

/**
 * `POST /api/v1/conversations/{conversation}/messages` — send a message on a
 * thread (US-CHT-03). Non-empty, length-capped; the cap is an Implementation
 * Assumption in config/chat.php.
 */
class StoreMessageRequest extends BaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.(int) config('chat.message_max_length', 4000)],
        ];
    }
}
