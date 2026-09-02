<?php

namespace Modules\Core\Exceptions\ApiException;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Modules\Core\Support\Traits\MakeAble;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class ExceptionAction
{
    use MakeAble;

    /**
     * @throws ApiException
     */
    public function __construct(Throwable $throwable, $code = 400)
    {
        throw ExceptionResponse::instance($throwable->getMessage(), $code);
    }

    /**
     * @throws ApiException
     */
    public static function modelNotFound(ModelNotFoundException $throwable): ExceptionResponse
    {
        $message = __(
            'exception.modelNotFound.no_query_results_for_model.not found any results for :model with request data :data',
            [
                'model' => __('exception.models.'.Str::snake(class_basename($throwable->getModel()))),
                'data' => implode(match (true) {
                    count($throwable->getIds()) > 0 => $throwable->getIds(),
                    count(request()->route()->parameters()) > 0 => request()->route()->parameters(),
                    default => implode(', ', request()->all()),
                }),
            ]
        );
        throw ExceptionResponse::instance($message);
    }

    /**
     * @throws ApiException
     */
    public static function unauthorized(): ExceptionResponse
    {
        $message = __('exception.unauthorized');
        throw ExceptionResponse::instance($message, ResponseCode::HTTP_FORBIDDEN)
            ->setCustomCode(4003);
    }

    /**
     * @throws ExceptionResponse
     */
    public static function transitionNotFound(TransitionNotFound $e): ExceptionResponse
    {

        $message = __('exception.transition-not-accepted', [
            'from' => __("exception.states.{$e->getFrom()}"),
            'to' => __("exception.states.{$e->getTo()}"),
        ]);
        throw ExceptionResponse::instance($message);
    }

    /**
     * @throws ApiException
     */
    public static function permissionDoesNotExist(PermissionDoesNotExist $throwable): ExceptionResponse
    {
        throw ExceptionResponse::instance("{$throwable->getMessage()} - please run permissions seeder to generate it");
    }

    /**
     * @throws ApiException
     */
    public static function typeError(\TypeError $throwable): ExceptionResponse
    {
        throw ExceptionResponse::instance(
            "{$throwable->getMessage()} in {$throwable->getFile()} line {$throwable->getLine()}"
        );
    }
}
