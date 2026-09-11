<?php

namespace Modules\Taxonomy\Filament\Resources\Units\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Units\UnitResource;

class CreateUnit extends CreateRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = UnitResource::class;
}
