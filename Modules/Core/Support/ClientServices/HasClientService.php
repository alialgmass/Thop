<?php

namespace Modules\Core\Support\ClientServices;

/**
 * @property string $serviceClass
 */
trait HasClientService
{
    public function service(): ClientService
    {
        return new $this->serviceClass($this);
    }
}
