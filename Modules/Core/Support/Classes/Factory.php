<?php

namespace Modules\Core\Support\Classes;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;
use Illuminate\Support\Str;

abstract class Factory extends BaseFactory
{
    public function modelName(): string
    {
        $model = Str::of(static::class)
            ->beforeLast('Factory')
            ->replace('Database\Factories', 'Models')
            ->toString();

        return $this->model ?? $model;
    }
}
