<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Modules\Core\Support\Contracts\Filters\FilterContract;

trait Filterable
{
    /**
     * @param  array<FilterContract>  $filters
     */
    public function scopeFilter(Builder $query, array $filters)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($filters)
            ->thenReturn();
    }
}
