<?php

namespace Modules\Taxonomy\Filament\Resources\Materials\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Materials\MaterialResource;

class EditMaterial extends EditRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = MaterialResource::class;
}
