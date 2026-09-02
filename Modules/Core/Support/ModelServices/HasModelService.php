<?php

namespace Modules\Core\Support\ModelServices;

trait HasModelService
{
    public function service(): ModelService
    {
        return new $this->serviceClass($this);
    }
}
