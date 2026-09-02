<?php

namespace Modules\Core\Enums;

enum HttpCodeEnum: int
{
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case INTERNAL_SERVER_ERROR = 500;
    case BAD_GATEWAY = 502;

    public static function successNotCompleted(): int
    {
        return self::ACCEPTED->value;
    }
}
