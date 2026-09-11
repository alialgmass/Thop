<?php

namespace Modules\Taxonomy\Filament\Resources\Colors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Taxonomy\Filament\Concerns\SavesThroughManageTaxonomyTerm;
use Modules\Taxonomy\Filament\Resources\Colors\ColorResource;

class CreateColor extends CreateRecord
{
    use SavesThroughManageTaxonomyTerm;

    protected static string $resource = ColorResource::class;
}
