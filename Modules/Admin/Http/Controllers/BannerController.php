<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Resources\BannerResource;
use Modules\Admin\Models\Banner;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;

/**
 * Public read for the separate marketplace client's homepage (Phase 9 · T6,
 * issue #35) — this repo has no homepage of its own. No authentication.
 */
class BannerController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $banners = Banner::query()->active()->orderBy('position')->get();

        return $this
            ->apiBody(['banners' => BannerResource::collection($banners)])
            ->apiResponse();
    }
}
