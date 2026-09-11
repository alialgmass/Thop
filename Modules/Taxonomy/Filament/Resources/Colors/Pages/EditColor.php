<?php

namespace Modules\Taxonomy\Filament\Resources\Colors\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Colors\ColorResource;

class EditColor extends EditRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = ColorResource::class;
}
