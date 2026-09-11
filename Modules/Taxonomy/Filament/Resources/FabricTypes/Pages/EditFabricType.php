<?php

namespace Modules\Taxonomy\Filament\Resources\FabricTypes\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\FabricTypes\FabricTypeResource;

class EditFabricType extends EditRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = FabricTypeResource::class;
}
