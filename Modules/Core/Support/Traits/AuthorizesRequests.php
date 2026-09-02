<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests as SourceAuthorizesRequests;

trait AuthorizesRequests
{
    use SourceAuthorizesRequests;

    protected array $customResourceMethodsWithoutModels = [];

    protected array $customResourceAbilityMap = [];

    public function resourceAbilityMap()
    {
        return array_merge([
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ], $this->customResourceAbilityMap);
    }

    protected function resourceMethodsWithoutModels()
    {
        return array_merge(['index', 'create', 'store'], $this->customResourceMethodsWithoutModels);
    }
}
