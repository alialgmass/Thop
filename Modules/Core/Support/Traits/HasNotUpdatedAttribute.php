<?php

namespace Modules\Core\Support\Traits;

trait HasNotUpdatedAttribute
{
    public function update(array $attributes = [], array $options = [])
    {
        $attributes = $this->resolveNotUpdatedAttribute($attributes);

        return parent::update($attributes, $options);
    }

    private function resolveNotUpdatedAttribute($attributes)
    {
        foreach ($attributes as $key => $value) {

            if (in_array($key, $this->notUpdatedAttributes())) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    abstract protected function notUpdatedAttributes(): array;
}
