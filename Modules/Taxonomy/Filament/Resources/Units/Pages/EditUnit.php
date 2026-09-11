<?php

namespace Modules\Taxonomy\Filament\Resources\Units\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Units\UnitResource;

class EditUnit extends EditRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = UnitResource::class;
}
