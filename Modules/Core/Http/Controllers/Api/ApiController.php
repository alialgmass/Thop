<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Str;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Core\Support\Traits\AuthorizesRequests;

/**
 * Class ApiController
 *
 * @property Authenticatable $user
 * @property int $perPage
 * @property bool $pagination
 * @property static string $model
 * @property static array $orderBy
 *
 * @author Hussein Zaher
 */
class ApiController extends BaseController
{
    use ApiResponse;
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public static array $orderBy = ['id' => 'desc'];

    public static ?string $model = null;

    protected ?int $perPage = 10;

    protected ?Authenticatable $user;

    protected bool $pagination = true;

    public function __construct()
    {
        $this->user = auth()->user();

        if (static::$model) {
            $this->authorizeResource(static::$model, Str::snake(class_basename(static::$model)));
        }
    }

    public static function label()
    {
        return __('app.'.Str::plural(Str::title(Str::snake(class_basename(static::class), ' '))));
    }

    public static function singularLabel()
    {
        return __('app.'.Str::singular(Str::title(Str::snake(class_basename(static::class), ' '))));
    }
}
