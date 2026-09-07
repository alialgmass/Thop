<?php

namespace Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Admin\Models\AuditLog;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor_id' => $this->actor_id,
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor?->id,
                'phone' => $this->actor?->phone,
            ]),
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
