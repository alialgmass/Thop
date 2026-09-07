<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Resources\AuditLogResource;
use Modules\Admin\Models\AuditLog;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Read-only viewer over the immutable audit trail (US-ADM-09). The gate is the
 * `admin` route middleware; there is deliberately no write action here — the
 * only write path is {@see AuditLog::record()}.
 */
class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('actor_id'), fn (Builder $query): Builder => $query->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('action'), fn (Builder $query): Builder => $query->where('action', $request->string('action')))
            ->when($request->filled('auditable_type'), fn (Builder $query): Builder => $query->where('auditable_type', $request->string('auditable_type')))
            ->when($request->filled('auditable_id'), fn (Builder $query): Builder => $query->where('auditable_id', $request->integer('auditable_id')))
            ->latest('id')
            ->paginate();

        $payload = AuditLogResource::collection($logs)->toResponse($request)->getData(true);

        return $this
            ->apiBody(['audit_logs' => $payload])
            ->apiResponse();
    }
}
