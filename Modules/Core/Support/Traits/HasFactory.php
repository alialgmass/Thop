<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Support\Str;
use Modules\Core\Support\Classes\Factory;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  mixed  $parameters
     */
    public static function factory(...$parameters): static|Factory
    {
        return self::getFactory()
            ->count(is_numeric($parameters[0] ?? null) ? $parameters[0] : null)
            ->state(is_array($parameters[0] ?? null) ? $parameters[0] : ($parameters[1] ?? []));
    }

    /**
     * Create a new factory instance for the model.
     */
    public static function getFactory()
    {
        $namespace = (string) Str::of(static::class)
            ->replace('Database\\Factories', 'Modules')
            ->replace('Models', 'Database\\Factories')
            ->append('Factory');

        return $namespace::new();
    }
}
