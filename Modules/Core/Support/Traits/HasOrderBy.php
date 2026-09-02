<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasOrderBy
{
    public function initializeHasOrderBy(): void
    {
        static::addGlobalScope('orderBy', function (Builder $query) {
            $query->when($this->sortBy ?? 'id', function (Builder $q, $sortBy) {
                return $q->orderBy($this->getTable().'.'.$sortBy, $this->sortDirection ?? 'desc');
            });
        });
    }
}
