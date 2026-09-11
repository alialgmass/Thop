<?php

namespace Modules\Taxonomy\Filament\Resources\Materials\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Materials\MaterialResource;

class CreateMaterial extends CreateRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = MaterialResource::class;
}
