<?php

namespace Modules\Admin\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Actions\ReactivateAccount;
use Modules\Admin\Actions\SuspendAccount;
use Modules\Admin\Http\Requests\SuspendAccountRequest;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Admin account moderation (US-ADM-07, Phase 9 · T8). The gate is the
 * `admin` route middleware, matching every other admin-only controller.
 * Only `suspend` requires `confirm=true` — the ticket's acceptance criteria
 * name that requirement for suspension only, not reactivation.
 */
class AdminAccountController extends Controller
{
    use ApiResponse;

    public function suspend(SuspendAccountRequest $request, User $account): JsonResponse
    {
        app(SuspendAccount::class)->handle($account, $request->user());

        return $this
            ->apiMessage('Account suspended.')
            ->apiResponse();
    }

    public function reactivate(Request $request, User $account): JsonResponse
    {
        app(ReactivateAccount::class)->handle($account, $request->user());

        return $this
            ->apiMessage('Account reactivated.')
            ->apiResponse();
    }
}
