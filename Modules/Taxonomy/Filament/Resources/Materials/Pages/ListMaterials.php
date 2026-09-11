<?php

namespace Modules\Taxonomy\Filament\Resources\Materials\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Taxonomy\Filament\Resources\Materials\MaterialResource;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
