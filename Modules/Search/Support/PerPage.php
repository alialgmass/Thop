<?php

namespace Modules\Search\Support;

/**
 * Shared pagination bounds for the search endpoints — an unbounded `per_page`
 * from the client is capped; a missing/invalid one falls back to the default.
 */
final class PerPage
{
    public const MAX = 50;

    public const DEFAULT = 20;

    public static function resolve(mixed $requested): int
    {
        $value = (int) $requested;

        return $value < 1 ? self::DEFAULT : min($value, self::MAX);
    }
}
