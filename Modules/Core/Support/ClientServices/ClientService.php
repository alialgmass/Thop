<?php

namespace Modules\Core\Support\ClientServices;

abstract class ClientService
{
    public function __construct(protected readonly object $client) {}
}
