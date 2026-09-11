<?php

namespace Modules\Taxonomy\Filament\Resources\FabricTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\FabricTypes\FabricTypeResource;

class CreateFabricType extends CreateRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = FabricTypeResource::class;
}
